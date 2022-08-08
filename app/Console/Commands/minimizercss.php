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
    public function handle()
    {
        $dom = new Dom;
        $dom->loadFromUrl('https://www.google.com');
        $html = $dom->outerHtml;
        //dd($html);
        $dom->loadStr($html);

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
        }

        foreach ($ids as $index =>$id) {
            $new_name = $this->check_name_exisist( $ids[$index]['new_name'], $ids);
            $ids[$index]['new_name'] = $new_name;
        }

            dd($classes, $ids);
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



}
