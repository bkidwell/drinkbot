<?php

class drinkbot extends module {

    public $title = "drinkbot";
    public $author = "Brendan Kidwell";
    public $version = "0.1";

    public function init() {
        $ini = new ini('modules/drinkbot/drinkbot.ini');

        $this->nick = $this->ircClass->getNick();
        $main = $ini->getSection('main');
        $this->catchphrase = trim($main['catchphrase']);
        $this->homepage = trim($main['homepage']);
        $this->min_delay = trim($main['min_delay']);
        $this->max_delay = trim($main['max_delay']);
        $this->drinks = array();
        foreach(array_keys($ini->getSection('drinks')) as $val) {
            $val = trim($val);
            if($val) { $this->drinks[] = $val; }
        }

        $this->re_find_catchphrase = '/\b' . $this->catchphrase . '\b/i';
        $this->re_find_drinks = '/\b(' . implode('|', $this->drinks) . ')\b/i';
        $this->re_find_greeting = '/^' . $this->nick . '[,: ].*?\b(bot|what|who|why|how|help|info)\b/i';
        $this->re_find_greeting2 = '/\b(bot|what|who|why|how|help|info)\b/i';

        $this->catch_quiet = 0;
        $this->greeting_quiet = 0;

        $this->to_send = array();
    }

    public function evt_message($line, $args) {
        $text = $line['text'];
        $is_private = $line['to'] == $this->nick;
        $t = time();

        if($this->catch_quiet < $t || $is_private) {
            if(preg_match($this->re_find_catchphrase, $text) == 1) {
                $this->reply($line, $this->drinks[array_rand($this->drinks)] . $this->get_suffix($text));
                if(!$is_private) { $this->catch_quiet = $t + 10; }
                return;
            }

            if(preg_match($this->re_find_drinks, $text) == 1) {
                $this->reply($line, $this->catchphrase . $this->get_suffix($text));
                if(!$is_private) { $this->catch_quiet = $t + 10; }
                return;
            }
        }

        if($this->greeting_quiet < $t || $is_private) {
            if(
                preg_match($this->re_find_greeting, $text) == 1 ||
                ($is_private && preg_match($this->re_find_greeting2, $text) == 1)
            ) {
                if(!$is_private) { $this->greeting_quiet = $t + 120; }
                $this->reply($line, 'I think about drinks a lot. ' . $this->homepage);
                return;
            }
        }

        //$this->reply($line, $line['text']);
    }

    private function get_suffix($text) {
        $c1 = substr_count($text, '!');
        $c2 = substr_count($text, '?');
        if($c1 > $c2) {
            if($c1 > 3) { $c1 = 3; }
            return str_repeat('!', $c1);
        }
        if($c2 > 0) {
            if($c2 > 3) { $c2 = 3; }
            return str_repeat('?', $c2);
        }
        return '';
    }

    private function reply($line, $text) {
        $is_private = $line['to'] == $this->nick;

        if($is_private) {
            $this->ircClass->privMsg($line['fromNick'], $text, $queue = 1);
            return;
        }

        $delay = rand($this->min_delay, $this->max_delay);
        $this->to_send[] = array(
            time() + $delay, $line['to'], $text
        );
        $this->evt_timer();
    }

    public function evt_timer() {
        $this->timerClass->removeTimer('drinkbot');

        $new_send = array();
        $next_tick = null;
        $t = time();
        foreach($this->to_send as $reply) {
            if(is_null($next_tick)) { $next_tick = $reply[0]; }
            if($next_tick > $reply[0]) { $next_tick = $reply[0]; }
            if($reply[0] <= $t) {
                $this->ircClass->privMsg($reply[1], $reply[2], $queue = 1);
            } else {
                $new_send[] = $reply;
            }
        }
        $this->to_send = $new_send;

        if(!is_null($next_tick)) {
            $interval = $next_tick - $t;
            if($interval < 0) { $interval = 0; }
            $this->timerClass->addTimer(
                'drinkbot', $this, 'evt_timer', '', $interval, false
            );
        }
    }
}

?>
