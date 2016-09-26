<?php
            $id_prefix = array();
                $id_prefix[0] = 'nm';
                $id_prefix[1] = 'tt';

            # get random id from the array
            $random_id_prefix = $id_prefix[array_rand($id_prefix)];

            $id_number = mt_rand(1,9999999);

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
            
            # Booleans for later conditional printing
            $is_movie = FALSE;
            $is_human = FALSE;
            # if starts with 'nm...' then go to the name/bio trivia section
            if ($random_id_prefix == $id_prefix[0]) {
                $build_url = file_get_contents("http://www.imdb.com/name/$id_prefix[0]$id_number/bio");
                preg_match_all('!(?<=(odd">)|(even">)).*(?=<br />)!siU', $build_url, $matches);
                $is_human = TRUE;
            }
            # otherwise go to the movie title trivia section
            else {
                $build_url = file_get_contents("http://www.imdb.com/title/$id_prefix[1]$id_number/trivia");
                preg_match_all('!(?<=sodatext">).*(?=  </div>)!siU', $build_url, $matches);
                $is_movie = TRUE;
            }

# up until: $match_count = count($matches[0]);

?>