<?php
/**
 * Twitter Feed Formatter File.
 *
 * PHP version 5.4
 *
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
namespace Concrete\Package\TweetFeedPackage\Src;

use Carbon\Carbon;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Twitter Feed Formatter Class.
 *
 * Utility class to expand a tweets entities and also tweak the
 * format the tweet object into more easily usable format.
 *
 * If you aren't familiar with tweet entities you may find this link useful:
 * https://dev.twitter.com/overview/api/entities-in-twitter-objects
 *
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
class TwitterFeedFormatter
{
    /**
     * CSS Classes for the various entities.
     *
     * @var array
     */
    protected $entity_css_classes = [
        'urls' => 'tf-url',
        'user_mentions' => 'tf-mention',
        'hashtags' => 'tf-hashtag',
        'media' => 'tf-media',
        'symbols' => 'tf-symbol',
    ];

    /**
     * Default expansion and format options.
     *
     * Each value corresponds to a tweet
     * entitiy type, except date.
     *
     * @var array
     */
    protected $default_options = [
        'urls',
        'user_mentions',
        'hashtags',
        'media',
        'symbols',
        'date',
    ];

    /**
     * Default formatters.
     *
     * Each entity type has a formatter to apply
     * on expansion, they are held here.
     *
     * @see getDefaultFormatters()
     * @var array
     */
    protected $formatters = [];

    /**
     * Constructor.
     *
     * @see getDefaultFormatters()
     * @param array $custom_formatters
     */
    public function __construct($custom_formatters = [])
    {
        $this->formatters = array_merge(
            $this->getDefaultFormatters(),
            $custom_formatters
        );
    }

    /**
     * Sets a custom formatter.
     *
     * @param string   $entity_type
     * @param callable $formatter
     */
    public function setCustomFormatter($entity_type, callable $formatter)
    {
        $this->formatters[$k] = $formatter;
    }

    /**
     * Gets the default expansion options.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->default_options;
    }

    /**
     * Sets the default expansion options.
     *
     * @param array $options
     */
    public function setDefaultOptions(array $options)
    {
        $this->default_options = $options;
    }

    /**
     * Gets the default entity formatters.
     *
     * @return array
     */
    protected function getDefaultFormatters()
    {
        return [
            'urls' => function ($url) {
                return '<a href="'.$url->expanded_url
                .'" target="_blank" class="'.$this->entity_css_classes['urls']
                .'">'.$url->display_url.'</a>';
            },

            'user_mentions' => function ($mention) {
                return '<a href="https://twitter.com/'.$mention->screen_name
                .'" target="_blank" class="'.$this->entity_css_classes['user_mentions']
                .'">@'.$mention->screen_name.'</a>';
            },

            'hashtags' => function ($hash_tag) {
                return '<a href="https://twitter.com/hashtag/'.$hash_tag->text
                .'?src=hash" target="_blank" class="'.$this->entity_css_classes['hashtags']
                .'">#'.$hash_tag->text.'</a>';
            },

            'media' => function ($media, $options = []) {
                if (in_array('links', $options)) {
                    return '<a href="'.$media->media_url.'" class="'
                    .$this->entity_css_classes['media'].'">'.$media->display_url.'</a>';
                }

                return '<img src="'.$media->media_url.'" alt="'.$media->display_url
                .'" class="'.$this->entity_css_classes['media'].'">';
            },

            'symbols' => function ($symbol) {
                return '<span class="'.$this->entity_css_classes['symbols'].'">'
                .$symbol->text.'</strong>';
            },
        ];
    }

    /**
     * Formats a tweet date.
     *
     * @param  string $date
     * @param  string $format [description]
     * @return string
     */
    public function formatDate($date, $format = 'diffForHumans')
    {
        $c = Carbon::createFromFormat('D M d H:i:s O Y', $date);
        if (method_exists($c, $format)) {
            return $c->$format();
        }

        return $c->format($format);
    }

    /**
     * Emoji Remover (until we add support for emojis).
     *
     * @param  string $text
     * @return string
     */
    protected function removeEmoji($text)
    {
        return preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $text);
    }

    /**
     * Multi-byte aware string replace function.
     *
     * @param  string $string
     * @param  string $replacement
     * @param  int $start
     * @param  int $length
     * @return string
     */
    protected function mb_substr_replace($string, $replacement, $start, $length = 0)
    {
        $str = mb_substr($string, 0, $start).$replacement;
        if ($length > 0) {
            $str .= mb_substr($string, $start + $length);
        }

        return $str;
    }

    /**
     * Gets a formatter for a specific entity type.
     *
     * @see    getDefaultFormatters()
     * @param  string $entity_type
     * @return callable
     */
    protected function getFormatter($entity_type)
    {
        return $this->formatters[$entity_type];
    }

    /**
     * Compiles the expanded HTML entity strings & extracts
     * the indices ready for replacement.
     *
     * @param  array    $entities
     * @param  callable $formatter
     * @param  array    &$replacements
     * @param  array    $formatter_opts
     * @return [array
     */
    protected function prepareEntities(
        array $entities,
        callable $formatter,
        array &$replacements,
        array $formatter_opts
    ) {
        foreach ($entities as $obj) {
            $replacements[] = [
                's' => $obj->indices[0],
                'e' => $obj->indices[1],
                'r' => $formatter($obj, $formatter_opts),
            ];
        }

        return $replacements;
    }

    /**
     * Prepares the entity replacements for each entity type
     * in the options.
     *
     * @param  object $tweet
     * @param  array $options
     * @return array
     */
    protected function prepareReplacements($tweet, $options)
    {
        $replacements = [];

        foreach ($options as $k) {
            $formatter_opts = [];
            if (is_array($k)) {
                $formatter_opts = array_slice($k, 1);
                $k = $k[0];
            }

            if (is_array($tweet->entities->$k) && count($tweet->entities->$k) > 0) {
                $this->prepareEntities(
                    $tweet->entities->$k,
                    $this->getFormatter($k),
                    $replacements,
                    $formatter_opts
                );
            }
        }

        return $replacements;
    }

    /**
     * Performs the expansion of the entities on the tweet content
     * from an array of replacements.
     *
     * @param  object $tweet
     * @param  array $replacements
     * @return string
     */
    protected function processReplacements($tweet, $replacements)
    {
        $tweet_txt = $tweet->text;
        usort($replacements, function ($a, $b) {
            return($b['s'] - $a['s']);
        });

        foreach ($replacements as $i) {
            $tweet_txt = $this->mb_substr_replace($tweet_txt, $i['r'], $i['s'], $i['e'] - $i['s']);
        }

        return $tweet_txt;
    }

    /**
     * Expands a tweets entities and returns
     * the expanded tweet text.
     *
     * @param  object $tweet
     * @param  array $options
     * @return string
     */
    public function expand($tweet, $options)
    {
        if (is_null($options) || ! is_array($options)) {
            $options = $this->default_options;
        }
        $replacements = $this->prepareReplacements($tweet, $options);

        return $this->processReplacements($tweet, $replacements);
    }

    /**
     * Formats a tweet by expanding the entities, humanizing the
     * date and peforming tweaks to the object structure.
     *
     * @param  object $tweet | array $tweets
     * @param  array $options
     * @return object $tweet | array $tweets
     */
    public function format()
    {
        $args = func_get_args();

        // Get or set options
        if (isset($args[1]) && is_array($args[1])) {
            $options = $args[1];
        } else {
            $options = $this->default_options;
        }

        if (is_array($args[0])) {
            foreach ($args[0] as $k => $tweet) {
                $args[0][$k] = $this->format($tweet, $options);
            }

            return $args[0];
        }

        $tweet = $args[0];

        // Has the tweet already been formated?
        if (isset($tweet->is_formatted)) {
            return $tweet;
        }

        // Use the original tweet rather than the retweet for processing
        if (is_object($tweet->retweeted_status)) {
            $tweet = $tweet->retweeted_status;
        }

        $expanded_tweet = $this->removeEmoji($this->expand($tweet, $options));
        $tweet->original_text = $tweet_txt;
        $tweet->text = $expanded_tweet;
        $tweet->avatar_url = $tweet->user->profile_image_url_https;
        $tweet->screen_name = '@'.$tweet->user->screen_name;
        $tweet->name = $tweet->user->name;
        $tweet->is_formatted = true;

        if (in_array('date', $options)) {
            $tweet->original_created_at = $tweet->created_at;
            $tweet->created_at = $this->formatDate($tweet->created_at);
        }

        return $tweet;
    }
}
