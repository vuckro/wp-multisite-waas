<?php

namespace WP_Ultimo\Dependencies\Mpdf\Language;

interface ScriptToLanguageInterface
{
    public function getLanguageByScript($script);
    public function getLanguageDelimiters($language);
}
