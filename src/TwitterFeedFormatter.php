<?php
namespace Concrete\Package\TwitterFeedPackage\Src;

use Core;
use Carbon\Carbon;

class TwitterFeedFormatter
{
    protected $entity_css_classes = array(
        'urls' => 'tf-url',
        'user_mentions' => 'tf-mention',
        'hashtags' => 'tf-hashtag',
        'media' => 'tf-media',
        'symbols' => 'tf-symbol'
    );

    protected $default_options = array(
        'urls',
        'user_mentions',
        'hashtags',
        'media',
        'symbols',
        'date',
    );

    protected $formatters = array();

    public function __construct($custom_formatters = array())
    {
        $this->formatters = array_merge(
            $this->getDefaultFormatters(), 
            $custom_formatters
        );
    }

    public function setCustomFormatter($entity_type, callable $formatter)
    {
        $this->formatters[$k] = $formatter;
    }

    public function getDefaultOptions()
    {
        return $this->default_options;
    }

    public function setDefaultOptions(array $options)
    {
        $this->default_options = $options;
    }

    public function formatDate($date, $format = 'diffForHumans')
    {
        $c = Carbon::createFromFormat('D M d H:i:s O Y', $date);
        if (method_exists($c, $format)) {
            return $c->$format();
        }
        return $c->format($format);
    }

    protected function getDefaultFormatters()
    {
        return array(
            'urls' => function ($url) {
                return '<a href="' . $url->expanded_url . '" target="_blank" class="' . $this->entity_css_classes['urls'] . '">' . $url->display_url . '</a>';
            },
            'user_mentions' => function ($mention) {
                return '<a href="https://twitter.com/' . $mention->screen_name . '" target="_blank" class="' . $this->entity_css_classes['user_mentions'] . '">@' . $mention->screen_name . '</a>';
            },
            'hashtags' => function ($hash_tag){
                return '<a href="https://twitter.com/hashtag/' . $hash_tag->text . '?src=hash" target="_blank" class="' . $this->entity_css_classes['hashtags'] . '">#' . $hash_tag->text . '</a>';
            },
            'media' => function ($media, $options = array()){
                if (in_array('links', $options)) {
                    return '<a href="' . $media->media_url . '" class="' . $this->entity_css_classes['media'] . '">' . $media->display_url . '</a>';
                }
                return '<img src="' . $media->media_url . '" alt="' . $media->display_url . '" class="' . $this->entity_css_classes['media'] . '">';
            },
            'symbols' => function ($symbol) {
                return '<span class="' . $this->entity_css_classes['symbols'] . '">' . $symbol->text .'</strong>';
            }
        );
    }

    protected function prepareEntities(array $entities, callable $formatter, array &$replacements, array $formatter_opts)
    {
        foreach($entities as $obj) {
                $replacements[] = array(
                    's' => $obj->indices[0],
                    'e' => $obj->indices[1],
                    'r' => $formatter($obj, $formatter_opts),
                );
            }
        return $replacements;
    }

    protected function processReplacements($tweet, $replacements)
    {
        $tweet_txt = $tweet->text;
        usort($replacements, function($a,$b){return($b['s']-$a['s']);});

        foreach ($replacements as $i) {
            $tweet_txt = substr_replace($tweet_txt, $i['r'], $i['s'], $i['e'] - $i['s']);
        }

        return $tweet_txt;
    }

    protected function getReplacements($tweet, $options)
    {
        $replacements = array();

        foreach ($options as $k) {

            $formatter_opts = array();
            if (is_array($k)) {
                $formatter_opts = array_slice($k, 1);
                $k = $k[0];
            }

            if (is_array($tweet->entities->$k) && count($tweet->entities->$k) > 0) {
                $this->prepareEntities($tweet->entities->$k, $this->getFormatter($k), $replacements, $formatter_opts);
            }
        }
        return $replacements;
    }

    protected function getFormatter($k)
    {
        return $this->formatters[$k];
    }

    public function expand($tweet, $options)
    {
        if (is_null($options) || !is_array($options)) {
            $options = $this->default_options;
        }
        $replacements = $this->getReplacements($tweet, $options);
        return $this->processReplacements($tweet, $replacements);
    }

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
                //var_dump($tweet);die;
                $args[0][$k] = $this->format($tweet, $options);    
            }
            return $args[0];
        } else {
            $tweet = $args[0];
        }

        $expanded_tweet = $this->expand($tweet, $options);
        $tweet->original_text = $tweet_txt;
        $tweet->text = $expanded_tweet;

        // Add the correct profile url
        if (is_object($tweet->retweeted_status)) {
            $tweet->avatar_url = $tweet->retweeted_status->user->profile_image_url_https;
            $tweet->screen_name = '@' . $tweet->retweeted_status->user->screen_name;
            $tweet->name = $tweet->retweeted_status->user->name;
        } else {
            $tweet->avatar_url = $tweet->user->profile_image_url_https;
            $tweet->screen_name = '@' . $tweet->user->screen_name;
            $tweet->name = $tweet->user->name;
        }

        if (in_array('date', $options)) {
            $tweet->original_created_at = $tweet->created_at;
            $tweet->created_at = $this->formatDate($tweet->created_at);
        }

        return $tweet;
    }
}
