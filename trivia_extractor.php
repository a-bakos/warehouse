<?php
/*
    IMDB TETbot
    Trivia Extractor and Tweeter

    The script uses external files that contain unique IDs from the IMDB system.
    For example, every movie and person has a unique ID which can be seen in the url of a given page.
    A file is picked randomly, so is a single item from that file.
    Then the proper url gets created according to that item (a movie's page or a person's bio),
    and the script extracts all of the trivia from the url's source code and stores them in an array.
    Also, searches for the title of the page (a movie title or a person's name).
    Checks if the value found is Twitter-friendly or not, and if so, it posts the title and the trivia
    to Twitter. If it is more than 140 character all together, the script gets reloaded and starts again.

    Created by Attila Bakos (abakos.info)
    2016, Plymouth, UK
*/

    # Page reload function
    function reload($delay = 1) {
        $site_url = $_SERVER['REQUEST_URI'];
        header("Refresh: " . $delay . "; URL=$site_url");
    }

    # Refresh the page with randomized timegaps (defined in seconds)
    reload(mt_rand(600,1800)); # currently: 10-30mins

    # Load and connect to Twitter API using TwitterOAuth - https://twitteroauth.com/
    require "twitteroauth/autoload.php";
    use Abraham\TwitterOAuth\TwitterOAuth;
    $connection = new TwitterOAuth("CONSUMER_KEY", "CONSUMER_SECRET", "access_token", "access_token_secret");

    # Load the files into an array for further processing
    $files = array();
        $files[0] = 'names.txt';
        $files[1] = 'movies.txt';

    # Get random a file from the array
    $random_trivia_file = $files[array_rand($files)];

    # Check which file is used
    if ($random_trivia_file == $files[0]) {
        $random_trivia_file = file_get_contents($files[0]);
    }
    else {
        $random_trivia_file = file_get_contents($files[1]);
    }

    # Create another array by exploding the random file at new lines
    # Now, every item from the file will be loaded into this array,
    # however, technically we will need only one of those at the end
    $random_item_array = explode("\n", $random_trivia_file);

    # Get a random value from the array with which the url can be built,
    # and trim the whitespaces
    # Example: tt0108778 (in movies.txt), nm0000151 (in names.txt)
    $random_item = $random_item_array[shuffle($random_item_array)];
    $random_item = trim($random_item);

    # Build the url based on which item has been chosen above,
    # then find trivia according to the regular expressions

    # Bio trivia looks like this:
    # <... class="sode odd | even">trivia here<br />
    # Movie title trivia looks like this:
    # <... class="sodatext">actual trivia here  </div> (note the two spaces)
    # In the regex pattern: (?<=...) will match things that is preceded by: sodatext">

    # Modifiers: siU
    # s => makes the period to match newlines (by default it doesn't)
    # i => case-insensitive search
    # U => non-greedy match

    # Booleans for later conditional printing
    $is_movie = FALSE;
    $is_human = FALSE;

    # If starts with 'nm...' complete the name/bio link
    if (preg_match('!nm.*!', $random_item, $random_name)) {
        $build_url = file_get_contents("http://www.imdb.com/name/$random_name[0]/bio");
        $target_url = "http://www.imdb.com/name/$random_item/bio";
        preg_match_all('!(?<=(odd">)|(even">)).*(?=<br />)!siU', $build_url, $matches);
        $is_human = TRUE;
    }
    # Otherwise complete the link to the movie's trivia
    else {
        $build_url = file_get_contents("http://www.imdb.com/title/$random_item/trivia");
        $target_url = "http://www.imdb.com/title/$random_item/trivia";
        preg_match_all('!(?<=sodatext">).*(?=  </div>)!siU', $build_url, $matches);
        $is_movie = TRUE;
    }

    # Count the matches 
    $match_count = count($matches[0]);

    # If no trivia has been found record the ID in a file and refresh the script
    $no_value = 'no_trivia.txt';
    if ($match_count == 0 || $match_count == NULL) {
        echo "<h2>No match or NULL value. Reload.</h2>";
        file_put_contents($no_value, $random_item . PHP_EOL, FILE_APPEND);
        reload();
    }
    else {
        if ($is_human == TRUE) {
            echo "<h2>Random person trivia -- 1 of $match_count:</h2>";
        }
        else { # ($is_movie = TRUE)
            echo "<h2>Random movie trivia -- 1 of $match_count:</h2>";
        }
    }

    # All the matches are stored in an array
    # Loop through the matches and trim the unnecessary whitespaces
    foreach ($matches[0] as $key => $value) {
        $matches[0][$key] = trim($value);
    }

    # Get the random trivia from the matches
    $random_trivia = $matches[0][shuffle($matches[0])];

    # Cut unnecessary HTML tags out
    $trivia_replace1 = preg_replace('!<a href=".*">!', "", $random_trivia);
    $trivia_replace2 = preg_replace('!(?=.*)</a>(?=.*)!', "", $trivia_replace1);

    # Find item title, that is actor name or movie title
    $proptitle = preg_match('!(?<=itemprop=\'url\'>).*(?=</a>)!', $build_url, $title_match);

    # Get the length of the title and the trivia and then add them together
    $title_length = strlen($title_match[0]);
    $trivia_length = strlen($trivia_replace2);
    $char_length = $trivia_length + $title_length + 1;

    # Hashtags
    define('MOVIE', '#movie');
    define('TRIVIA', '#trivia');

    # Check if the end value is Twitter-friendly
    # (Twitter URL shortener creates 23 character long addresses)
    # If the title + trivia is less than 101 chars, append two hashtags and the current URL to it
    if ($char_length <= 101) {
        $tweet = $title_match[0] . ": " . $trivia_replace2 . " " . MOVIE . " " . TRIVIA . " " . $target_url;
        $statuses = $connection->post("statuses/update", ["status" => $tweet]);
    }
    # If the title + trivia is between  106 and 117 chars, tweet with 1 hashtag and a link
    elseif ($char_length >= 106 && $char_length <= 117) {
        $tweet = $title_match[0] . ": " . $trivia_replace2 . " " . TRIVIA . " " . $target_url;
        $statuses = $connection->post("statuses/update", ["status" => $tweet]);
    }
    # If the title + trivia is between  120 and 138 chars, tweet without link and hashtag
    elseif ($char_length >= 118 && $char_length <= 138) {
        $tweet = $title_match[0] . ": " . $trivia_replace2;
        $statuses = $connection->post("statuses/update", ["status" => $tweet]);
    }
    # Or reload...
    else {
        reload();
    }

    # Print the things out
    # length:
    echo "<h1>Char count: " . $char_length . "</h1>";
    # title:
    echo "<h1>Title: " . $title_match[0] . "</h1>";
    echo "<hr />";
    # trivia:
    echo "<h2>Random trivia:</h2>";
    echo "<h2>This goes on Twitter: " . $tweet . "</h2>";
    echo "<p>" . $trivia_replace2 . "</p>";
    echo "<p>URL in use:" . $target_url . "</p>";
?>