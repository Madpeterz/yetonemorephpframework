<?php

if (isset($dbObjectsLoadPath) == false) {
    $inputFilter_LoadPath = dirname(__FILE__) . "/";
}
include $inputFilter_LoadPath . "types/base.php";
include $inputFilter_LoadPath . "filters/array.php";
include $inputFilter_LoadPath . "filters/string.php";
include $inputFilter_LoadPath . "filters/integer.php";
include $inputFilter_LoadPath . "filters/float.php";
include $inputFilter_LoadPath . "filters/bool.php";
include $inputFilter_LoadPath . "filters/uuid.php";
include $inputFilter_LoadPath . "filters/json.php";
include $inputFilter_LoadPath . "filters/http.php";
include $inputFilter_LoadPath . "filters/email.php";
include $inputFilter_LoadPath . "filters/date.php";
include $inputFilter_LoadPath . "filters/vector.php";
include $inputFilter_LoadPath . "filters/checkbox.php";
include $inputFilter_LoadPath . "filters/color.php";
include $inputFilter_LoadPath . "types/value.php";
include $inputFilter_LoadPath . "types/get.php";
include $inputFilter_LoadPath . "types/post.php";
include $inputFilter_LoadPath . "types/final.php";

// filter_array, filter_string,filter_integer,filter_float,filter_bool,filter_uuid,filter_json,filter_http
// filter_email,filter_date,filter_vector, filter_checkbox,filter_color
// valueFilter, getFilter, postFilter
