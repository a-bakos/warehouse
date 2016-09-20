        <?php
            # trivia extractor -- abakos

            # set title number
            $tt_number = "tt3385516";

            # read the file/url
            $file = file_get_contents("http://www.imdb.com/title/$tt_number/trivia");
            
            # find trivia
            # trivia looks like this:
            # <... class="sodatext">actual trivia here  </div> -- trivia followed by 2 spaces
            
            # in the regex pattern: ?<= will match trivia that is preceded by: sodatext">
            # modifiers: siU
            # s => makes the period to match newlines [by default it doesn't]
            # i => case-insensitive search
            # U => non-greedy match
            $ext = preg_match_all('!(?<=sodatext">).*  </div>!siU', $file, $matches);

            # loop through the matches array and trim the unnecessary whitespaces
            foreach ($matches[0] as $key => $value) {
                $matches[0][$key] = trim($value);
            }

            # find item title
            $proptitle = preg_match('!(?<=itemprop=\'url\'>).*</a>!', $file, $match);
            echo "<h1>Title: " . $match[0] . "</h1>";
            echo "<hr />";

            # print random trivia from the list
            echo "<h2>Random trivia:</h2>";
            $random_trivia = $matches[0][array_rand($matches[0])];
            echo $random_trivia;

            # print the array elements / trivia
            # print_r(expression, return boolean)
            # when this bool parameter is set to TRUE,
            # print_r() will return the information rather than print it
            echo "<h2>All trivia:</h2>";
            echo "<pre>" . print_r($matches, 1) . "</pre>";
        ?>