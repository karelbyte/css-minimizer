<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
        $classes =  preg_match_all('/class="\s*(.*?)\s*"/s', $html, $classes, PREG_SET_ORDER, 0);
        $ids = preg_match_all('/id="\s*(.*?)\s*"/s', $html, $classes, PREG_SET_ORDER, 0);


        var_dump($ids, $classes);
    }
}
