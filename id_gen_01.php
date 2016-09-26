<!DOCTYPE html>
<html>
    <body>
        <?php
            set_time_limit(0);
            $id_list = "id_movies_01.txt";
            $prefix = "tt"; # or "nm"

            for ($i = 0; $i <= 999999; $i++) {
                $id_number = $i + 1;

                if (strlen($id_number) < 2) {
                    $id_number = "000000" . $id_number;
                }
                elseif (strlen($id_number) < 3) {
                    $id_number = "00000" . $id_number;
                }
                elseif (strlen($id_number) < 4) {
                    $id_number = "0000" . $id_number;
                }
                elseif (strlen($id_number) < 5) {
                    $id_number = "000" . $id_number;
                }
                elseif (strlen($id_number) < 6) {
                    $id_number = "00" . $id_number;
                }
                elseif (strlen($id_number) < 7) {
                    $id_number = "0" . $id_number;
                }

                file_put_contents($id_list, $prefix . $id_number . PHP_EOL, FILE_APPEND);    
            }

            echo "the first script has run.\n";
        ?>
    </body>
</html>