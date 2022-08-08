<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom;

class minimizercss extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:minimizercss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command will minify the css code inside the external pages for can be created amp pages';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(){
        $dom = new Dom;
        $dom->loadFromUrl('https://ellibrodepython.com/');
        $html = $dom->outerHtml;
        //dd($html);
        $html_string = $dom->loadStr($html);

         preg_match_all('/class="\s*(.*?)\s*"/s', $html, $obtained_classes, PREG_SET_ORDER, 0);
        preg_match_all('/id="\s*(.*?)\s*"/s', $html, $obtained_ids, PREG_SET_ORDER, 0);
        $classes = $obtained_classes;
        $ids = $obtained_ids;
        foreach ($classes as $index =>$class) {
            $class = $class[1];
            $classes[$index]['new_name'] = substr($class, 0, 1);
        }
        foreach ($ids as $index =>$id) {
            $id = $id[1];
            $ids[$index]['new_name'] = substr($id, 0, 1);
        }

        foreach ($classes as $index =>$class) {
            $new_name = $this->check_name_exisist( $classes[$index]['new_name'], $classes);
            $classes[$index]['new_name'] = $new_name;
            $original_element =  $html_string->find('.' . $classes[$index][1])[0];
            if($original_element){
                $original_element->setAttribute('class', $classes[$index]['new_name']);
            }

        }

        foreach ($ids as $index =>$id) {
            $new_name = $this->check_name_exisist( $ids[$index]['new_name'], $ids);
            $ids[$index]['new_name'] = $new_name;
            $original_element =  $html_string->find('#' . $ids[$index][1])[0];
            if($original_element){
                $original_element->setAttribute('id', $ids[$index]['new_name']);
            }
        }


        $this->css_to_minimizer($html, $ids, $classes);

    }

    private function check_name_exisist($name, $classes){
        $new_name = $name;
        $i = 1;
        while (true) {
            $found = false;
            foreach ($classes as $index=>$class) {
                if ($class['new_name'] == $new_name) {
                    $found = true;
                    $new_name = $name .$i;
                    $i++;
                    break;
                }
            }
            if (!$found) {
                break;
            }
        }
        return $new_name;
    }


    protected function css_to_minimizer($html, $ids, $classes) {
        $css_minimized_list = collect([]);
        preg_match_all('/<link rel="stylesheet" href="(.*?)" \/>/', $html, $links, PREG_SET_ORDER, 0);
        $links = collect($links)->flatten()->filter(fn ($link) => strpos($link, "https") === 0);
        $links->each(function ($link) use ($css_minimized_list, $ids, $classes){
           $css_original = file_get_contents($link);
           $css_original_name_replaced = $this->css_class_name_replaced($css_original, $ids, $classes);
           $css_minimized = $this->minimize_css($css_original_name_replaced);
           $css_minimized_list->push(['url' => $link, 'css_minimized' => $css_minimized]);
        });

        dump($css_minimized_list);
    }


    protected function css_class_name_replaced($css, $ids, $classes){

        dd($css);

        foreach ($ids as $id) {
            $css = preg_replace('/'.$id[1].'/', $id['new_name'], $css);
        }

        foreach ($classes as $class) {
            $css = preg_replace('/'.$class[1].'/', $class['new_name'], $css);
        }

        dd($css);
        return $css;
    }

    protected function minimize_css($css){
        $css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css);
        $css = preg_replace('/\s{2,}/', ' ', $css);
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
        $css = preg_replace('/;}/', '}', $css);
        return $css;
    }


}
