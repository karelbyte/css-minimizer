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
    protected $signature = 'app:minimizercss {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will minify the css code inside the external pages for can be created amp pages';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(){
        $dom = new Dom;
        $dom->loadFromUrl($this->argument('url'));
        $html = $dom->outerHtml;

        $html_string = $dom->loadStr($html);

        preg_match_all('/class="\s*(.*?)\s*"/s', $html, $obtained_classes, PREG_SET_ORDER, 0);
        preg_match_all('/id="\s*(.*?)\s*"/s', $html, $obtained_ids, PREG_SET_ORDER, 0);

        $classes = $this->get_classes($obtained_classes);
        $classes = $this->unique_multidimensional_array($classes, 1);
        $classes = $this->get_new_name_classes($classes);
        $html = $this->html_class_name_replaced($html, $classes);
        $ids = $this->get_ids($obtained_ids);
        $ids = $this->get_new_name_ids($ids);
        $html = $this->html_id_name_replaced($dom,$html, $ids);
        file_put_contents('./output/index.html', $html);
        $this->css_to_minimizer($html, $ids, $classes);

        dump('Process finished');

    }

    private function get_classes($obtained_classes){
        $classes = collect();
        foreach ( $obtained_classes as $index =>$class) {
            if (strpos($class[1]," ")) {
                $class_array = explode(" ", $class[1]);
                // Loop through the array and add the structure to the collection
                foreach ($class_array as $key=>  $item) {
                    if ($key == 0)  $parent_key = '';  else  $parent_key = $index;
                        $class = [
                            0 => $obtained_classes[$index][1],
                            1 => $item,
                            'original'  =>  $obtained_classes[$index][1],
                            'is_child' => $key != 0,
                            'parent' =>$parent_key,
                        ];
                    $class_name = $item;
                    $class['new_name'] = substr($class_name, 0, 1);
                    $classes->push($class);
                }
            }
            else{
                $class_name = $class[1];
                $class[0] = str_replace("class=", "", $class[0]);
                $class['original'] = $class[1];
                $class[ 'is_child'] = false;
                $class['parent' ] = '';
                $class['new_name'] = substr($class_name, 0, 1);
                $classes->push($class);
            }
        }
        return $classes;
    }

    /**
     * @param $classes
     * @return array
     *  This function will get the unique values of the classes array
     */
    protected function unique_multidimensional_array($array, $key) {
        $temp_array = collect();
        $i = 0;
        $key_array = array();
        // Loop through each array element and add it to the temp array as long as it does not already exist in the temp array
        $array->each(function ($item) use (&$temp_array, &$key_array, &$i, $key) {
            if (!in_array($item[$key], $key_array)) {
                $key_array[$i] = $item[$key];
                $temp_array->push($item);
                $i++;
            }
        });
        return $temp_array;
    }
    /**
     * @param $obtained_classes
     * @return array
     * This function will get the classes from the html code and will change the name of the classes
     */

    private function get_new_name_classes( $classes) {
        $classes = $classes->toArray();
        $i = 0;
        // Loop through each array element and add  the new name to the class name
        foreach ($classes as $index=>$class) {
            $new_name = $class['new_name'] . $i;
            $classes[$index]['new_name'] = $new_name;
            if ($class['is_child'] == true) {
                // Get the parent class name and add the new name to the end of it
                $parent = array_map(function ($item) use ($class) {
                    if (substr($item['new_name'],0,1) == substr($class['new_name'],0,1)) {
                        return $item['new_name'];
                    }
                    if ($item['original'] == $class['original'] && $item['is_child'] == false) {
                        return $item['new_name'];
                    }
                }, $classes);
                $parent = array_filter($parent);
                $parent = array_values($parent);
                if (count($parent) > 0) {
                    $classes[$index]['parent'] =$parent[0];
                }
            }
            $i++;
        }

        return $classes;
    }

    private function get_ids($obtained_ids){
        $ids = [] ;
        foreach ($obtained_ids as $index =>$id) {
            if (strpos($id[1]," ")) {
                $id_array = explode(" ", $id[1]);
                foreach ($id_array as $index => $item) {
                    $id = [
                        0 => 'id='.$item,
                        1 => $item,
                    ];
                    $id_name = $item;
                    $id['new_name'] = substr($id_name, 0, 1);
                    array_push($ids, $id);
                }
            }
            else{
                $id_name = $id[1];
                $id['new_name'] = substr($id_name, 0, 1);
                array_push($ids, $id);
            }
        }
        return $ids;
    }

    /**
     * @param array $classes
     * @return array
     * This function will get the classes from the html code and will change the name of the classes
     */
    private function html_class_name_replaced($html, array $classes){
        foreach ($classes as $index =>$class) {
            $new_name = $class['new_name'];

            if ($index > 0) {

                if ( $class[0] === $class['original'] && $class['is_child'] == true) {
                    $html = preg_replace('/'.$class[0].'/', $class["parent"]." ".$new_name, $html);
                }
                else{
                    $html = preg_replace('/'.$class[0].'/', $new_name, $html);
                }
            }
        }
        return $html;
    }

    private function get_name_id($name, $ids){
        $new_name = $name;
        $i = 1;
        while (true) {
            $found = false;
            foreach ($ids as $index=>$class) {
                if ($class['new_name'] == $new_name) {
                    $found = true;
                    $new_name = $name .$i;
                    $i++;
                    break;
                }
            }
            if (!$found) break;
        }
        return $new_name;
    }

    private function get_new_name_ids(array $ids){
        foreach ($ids as $index =>$id) {
            $new_name = $this->get_name_id( $ids[$index]['new_name'], $ids);
            $ids[$index]['new_name'] = $new_name;
        }
        return $ids;
    }

    /**
     * @param $html
     * @param array $ids
     * @return mixed
     * This function will get the ids from the html code and will change the name of the ids
     */
    private function html_id_name_replaced($dom,$html_string, array $ids){
        $html_string = $dom->loadStr($html_string);

        foreach ($ids as $index =>$id) {
            $original_element =  $html_string->find('#' . $ids[$index][1])[0];
            if($original_element){
                $original_element->setAttribute('id', $ids[$index]['new_name']);
            }
        }
        $html = $dom->outerHtml;
        return $html;
    }


    protected function css_to_minimizer($html, $ids, $classes){
        $css_minimized = '';
        preg_match_all('/<link rel="stylesheet" href="(.*?)" \/>/', $html, $links, PREG_SET_ORDER, 0);
        $links = collect($links)->flatten()->filter(fn ($link) => strpos($link, "https") === 0);
        $links->each(function ($link) use (&$css_minimized, $ids, $classes){
           $css_original = file_get_contents($link);
           $css_original_name_replaced = $this->css_class_name_replaced($css_original, $ids, $classes);
           $css_minimized .= $this->minimize_css($css_original_name_replaced);
        });
        file_put_contents('./output/css_minimized.css',  $css_minimized);
        return $css_minimized;
    }


    protected function css_class_name_replaced($css, $ids, $classes){

        foreach ($ids as $id) {
            $css = preg_replace('/'.$id[1].'/', $id['new_name'], $css);
        }

        foreach ($classes as $class) {
            $css = preg_replace('/'.$class[1].'/', $class['new_name'], $css);
        }

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
