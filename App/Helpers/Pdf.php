<?php
namespace App\Helpers;

use Knp\Snappy\Pdf as Snappy;
use App\Config\Configurations as cfg;

class Pdf
{

    public $url = '';
    public $number;
    private $urlBase = 'http://' . cfg::DOMAIN;

    public function gerar()
    {
        $url = $this->urlBase . $this->url;
        $number = $this->number ?? time();
        $snappy = new Snappy('/usr/bin/wkhtmltopdf');
        $snappy->setOptions([
            'orientation' => 'Landscape',
            //'default-header' => true,
            //'user-style-sheet' => true
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="solicitacao_' . $number . '.pdf"');
        echo $snappy->getOutput($url);
    }
}
