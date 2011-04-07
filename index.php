<?php

define('POSTS_DIR', 'posts');
define('SALT', 'NaCl');
date_default_timezone_set('UTC');

function delete_token($offset = 0)
{
	return sha1((int)(time() / (24 * 60 * 60)) + $offset . SALT);
}

function posts_pattern()
{
	return sprintf("%s/*.post", POSTS_DIR);
}

function timestamp_from_postfile($postfile)
{
	$pattern = sprintf('/^%s\/(\d+)\.post$/', preg_quote(POSTS_DIR, '/'));
	if(!preg_match($pattern, $postfile, $matches))
	{
		throw new RuntimeException(sprintf("Couldn't get timestamp from post file %s.", $postfile));
	}
	return $matches[1];
}

// Check for existence of posts directory
if(!file_exists(POSTS_DIR) || !is_dir(POSTS_DIR))
{
	// Directory doesn't exist; create it.
	if(!mkdir(POSTS_DIR))
	{
		throw new RuntimeException("Couldn't create posts directory");
	}
}

// Check posts directory is writable
if(!is_writable(POSTS_DIR))
{
	throw new RuntimeException("Posts directory is not writable");
}


if($_SERVER["REQUEST_METHOD"] == "POST")
{
	if(isset($_POST["postsuckerdelete"]) &&
			in_array($_POST["postsuckerdelete"], array(delete_token(), delete_token(-1))))
	{
		// Delete all posts
		foreach (glob(posts_pattern()) as $postfile)
		{
			if(!unlink($postfile))
			{
				throw new RuntimeException(sprintf("Couldn't delete %s", $postfile));
			}
		}
	}
	else
	{
		file_put_contents(
			sprintf("%s/%s.post", POSTS_DIR, time()),
			http_build_query($_POST)
		);
	}
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Postsucker</title>
	<link rel="stylesheet" href="http://craiga.id.au/main.css" type="text/css">
</head>
<body>
	<h1>Postsucker</h1>
	<?php foreach(glob(posts_pattern()) as $postfile): ?>
		<h2>Post at
			<?php echo htmlspecialchars(date('r', timestamp_from_postfile($postfile))); ?>
			(<a href="http://unixtimesta.mp/<?php echo htmlspecialchars(timestamp_from_postfile($postfile)); ?>"><?php echo htmlspecialchars(timestamp_from_postfile($postfile)); ?></a>)
		</h2>
		<pre class="code"><?php echo htmlspecialchars(file_get_contents($postfile)); ?></pre>
	<?php endforeach; ?>
	<form method="post">
		<input type="hidden" name="postsuckerdelete" value="<?php echo htmlspecialchars(delete_token()); ?>" />
		<input type="submit" value="Clear Posts" />
	</form>
	<div id="foot">
		<p>A <a href="http://craiga.id.au/">Craig Anderson</a> joint. <a href="https://github.com/craiga/postsucker">Get the code on GitHub.</a></p>
	</div>
</body>
</html>