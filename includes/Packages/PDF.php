<?php

namespace WOAP\Packages;

use TCPDF;
use TCPDF_FONTS;


class PDF extends TCPDF {

    protected $document_details;

    public function __construct() {
        call_user_func_array(['parent','__construct'], func_get_args());
        $blog_name = get_option('blogname');
        $blog_description = get_option('blog_description');
        $this->document_details = [
            'creator' => PDF_CREATOR,
            'author' => $blog_name,
            'title' => $blog_name,
            'subject' => $blog_description,
            'keywords' => [$blog_name],
            'fonts' => [
                'regular' => '',
                'bold' =>  '',
            ],
            'header' => [
                'color' => [0,0,0],
                'background' => [
                    'source' => '',
                    'position' => [],
                    'size' => [],
                ],
            ],
            'footer' => [
                'color' => [0,0,0],
                'background' => [
                    'source' => '',
                    'position' => [],
                    'size' => [],
                ],
            ],
            'logo' => [
                'source' => '',
                'position' => [],
                'size' => [],
            ],
            'language' => [
                'a_meta_charset' => 'UTF-8',
                'a_meta_dir' => 'rtl',
                'a_meta_language' => 'fa',
                'w_page' => 'page',
            ],
            'margin' => 25,
        ];
    }

    public function set_document_details( $document_details ) {
        foreach($document_details as $key => $value){
            $this->document_details[$key]=$value;
        }
        [
            'creator' =>  $creator,
            'author' => $author,
            'title' => $title,
            'subject' => $subject,
            'keywords' => $keywords,
            'language' => $language,
            'margin' => $margin,
            'fonts' => $fonts,
        ] = $this->document_details;
        $fontname = TCPDF_FONTS::addTTFfont( $fonts['regular'] , 'TrueTypeUnicode', 'regular', 96);
        $bold_fontname = TCPDF_FONTS::addTTFfont( $fonts['bold'] , 'TrueTypeUnicode', 'bold', 96);
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->SetCreator($creator);
        $this->SetAuthor($author);
        $this->SetTitle($title);
        $this->SetSubject($subject);
        $this->SetKeywords(implode(',',$keywords));
        $this->SetMargins(PDF_MARGIN_LEFT, $margin , PDF_MARGIN_RIGHT);
        $this->setLanguageArray($language);
        $this->SetFont($bold_fontname, '', 10, '', false);
        $this->SetFont($fontname, '', 12, '', false);
    }

    public function get_document_details(string $key=null){
        return ($key)? $this->document_details[$key] : $this->$document_details;
    }

    public function Header() {
        $break_margin = $this->getBreakMargin();
        $auto_page_break = $this->AutoPageBreak;
        $this->SetAutoPageBreak(false, 0);

        [
            'color' => [$red,$green,$blue],
            'background' => [
                'source' => $background_source,
                'position' => [$background_x,$background_y],
                'size' => [$background_width,$background_height]
            ]
        ] = $this->get_document_details('header');

        [
            'source' => $logo_source,
            'position' => [$logo_x,$logo_y],
            'size' => [$logo_width,$logo_height]
        ] = $this->get_document_details('logo');

        ['regular' => $font_regular] = $this->get_document_details('fonts');

        $this->SetTextColor($red,$green,$blue);
		$true_type_font = TCPDF_FONTS::addTTFfont( $font_regular , 'TrueTypeUnicode', '', 96);
        $this->SetFont($true_type_font);
        $this->Image($background_source, $background_x, $background_y, $background_width, $background_height);
        $this->Image($logo_source, $logo_x,$logo_y,$logo_width,$logo_height);
        $this->SetAutoPageBreak($auto_page_break, $break_margin);
        $this->setPageMark();
    }

    public function Footer() {
        $break_margin = $this->getBreakMargin();
        $auto_page_break = $this->AutoPageBreak;
        $this->SetAutoPageBreak(false, 0);

        [
            'color' => [$red,$green,$blue],
            'background' => [
                'source' => $background_source,
                'position' => [$background_x,$background_y],
                'size' => [$background_width,$background_height]
            ]
        ] = $this->get_document_details('footer');

        ['regular' => $font_regular] = $this->get_document_details('fonts');

        $this->Image($background_source, $background_x, $background_y, $background_width, $background_height);
        [$red,$green,$blue] = $this->get_document_details('footer')['color'];
        $this->SetTextColor($red,$green,$blue);
		$true_type_font = TCPDF_FONTS::addTTFfont( $font_regular , 'TrueTypeUnicode', '', 96);
        $this->SetFont($true_type_font);
        $this->SetAutoPageBreak($auto_page_break, $break_margin);
        $this->setPageMark();
    }

}
