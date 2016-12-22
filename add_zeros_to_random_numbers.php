<?php

  $random_number = mt_rand(1,9999999);
  $random_number_digits = strlen($random_number);
    
if ($random_number_digits < 7) {
  $digits_calc = 7 - $random_number_digits;
  $add_zeros = str_repeat("0", $digits_calc);
  $random_number = $add_zeros . $random_number;
}
else {
  $digits_calc = "0";
}

echo $digits_calc . " zero(s) added";
echo "<br/>";
echo $random_number;

?>