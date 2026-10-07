<?php

return [

    'email_varified'    => false,
    'secret_token'      => 'k9l3xJuL6D9dBmvPIDMe6Th3Wj8WpzeJKvDbcBU4vgsdfgvdgdfN6DOVXmZzgKHEZ2hPYdGsyhhJdmCWzvFkGpl',
    'phoneRegExp'       => "/^(?:(?:\+|0{0,2})91(\s*|[-])?|[0]?)?([6789]\d{2}([ -]?)\d{3}([ -]?)\d{4})$/",
    'emailRegExp'       => '/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i',
    'gstinRegExp'       => "/\d{2}[A-Z]{5}\d{4}[A-Z]{1}[A-Z\d]{1}[Z]{1}[A-Z\d]{1}/",

    'setting_array'     => [
        '1'             => 'General Settings',
        '2'             => 'Social Links Setting',
        '3'             => 'Mail Setting',
    ],







];
