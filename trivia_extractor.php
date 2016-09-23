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
    to Twitter. If it is more than 140 character combined, the script gets reloaded and starts again.
    
    Created by Attila Bakos (abakos.info)
    2016, Plymouth, UK
*/

    # Refresh the page with randomized timegaps (defined in seconds)
    $site_url = $_SERVER['REQUEST_URI'];
    header("Refresh: " . mt_rand(600,1800) . "; URL=$site_url"); # currently: 10-30mins

    # Load and connect to Twitter API using TwitterOAuth - https://twitteroauth.com/
    require "twitteroauth/autoload.php";
    use Abraham\TwitterOAuth\TwitterOAuth;
    $connection = new TwitterOAuth("CONSUMER_KEY", "CONSUMER_SECRET", "access_token", "access_token_secret");

    # Load the files into an array for further processing
    $trivia_files = array();
        $trivia_files[0] = 'names.txt';
        $trivia_files[1] = 'movies.txt';

    # Get random a file from the array
    $random_trivia_file = $trivia_files[array_rand($trivia_files)];

    # Check which file is used
    if ($random_trivia_file == $trivia_files[0]) {
        $random_trivia_file = file_get_contents($trivia_files[0]);
    }
    else {
        $random_trivia_file = file_get_contents($trivia_files[1]);
    }

    # Create another array by exploding the random file at new lines
    # Now, every item from the file will be loaded into this array,
    # however, technically we will need only one of those at the end
    $random_item_array = explode("\n", $random_trivia_file);

    # Get a random value from the array with which the url can be built,
    # and trim the whitespaces
    # Example: tt0108778 (in movies.txt), nm0000151 (in names.txt)
    $random_item = $random_item_array[array_rand($random_item_array)];
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

    # If starts with 'nm...' complete the name/bio link
    if (preg_match('!nm.*!', $random_item, $random_name)) {
        $build_url = file_get_contents("http://www.imdb.com/name/$random_name[0]/bio");
        $ext = preg_match_all('!(?<=(odd">)|(even">)).*(?=<br />)!siU', $build_url, $matches);
        echo "<h2>Random trivia from actors:</h2>";
    }
    # Otherwise complete the link to the movie's trivia
    else {
        $build_url = file_get_contents("http://www.imdb.com/title/$random_item/trivia");
        $ext = preg_match_all('!(?<=sodatext">).*(?=  </div>)!siU', $build_url, $matches);
        echo "<h2>Random trivia from movies:</h2>";
    }

    # All the matches are stored in an array
    # Loop through the matches and trim the unnecessary whitespaces
    foreach ($matches[0] as $key => $value) {
        $matches[0][$key] = trim($value);
    }

    # Get the random trivia from the matches
    $random_trivia = $matches[0][array_rand($matches[0])];

    # Cut unnecessary HTML tags out
    $trivia_replace1 = preg_replace('!<a href=".*">!', "", $random_trivia);
    $trivia_replace2 = preg_replace('!(?=.*)</a>(?=.*)!', "", $trivia_replace1);

    # Find item title, that is actor name or movie title
    $proptitle = preg_match('!(?<=itemprop=\'url\'>).*(?=</a>)!', $build_url, $title_match);

    # Get the length of the title and the trivia and then add them together
    $title_length = strlen($title_match[0]);
    $trivia_length = strlen($trivia_replace2);
    $char_length = $trivia_length + $title_length + 1;

    # Check if the end value is Twitter-friendly
    # If yes, then tweet it, if not refresh the page
    if ($char_length <= 140) {
        $statuses = $connection->post("statuses/update", ["status" => $title_match[0] . ": " . $trivia_replace2]);
    }
    else {
        header("Refresh: 1; URL=$site_url");
    }

    # Print the things out
    # length:
    echo "<h1>Char count: " . $char_length . "</h1>";
    # title:
    echo "<h1>Title: " . $title_match[0] . "</h1>";
    echo "<hr />";
    # trivia:
    echo "<h2>Random trivia:</h2>";
    echo "<p>" . $trivia_replace2 . "</p>";
?>