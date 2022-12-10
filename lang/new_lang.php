<?php

echo "<?php\n";
echo "\$lang_data=array(\n";

if(!is_dir("lang"))
{
	chdir("..");
}

$dir=opendir(".");
$total=array();
while($file=readdir($dir)) {
	if(!ereg(".php$", $file) || $file=="new_lang.php")
		continue;

	$contents=file_get_contents($file);
	preg_match_all('/\_\_\(("[^"]*")\)/', $contents, & $matches);
	$total=array_merge($total, $matches[1]);
}

sort($total);

$first=true;
foreach($total as $match)
{
	if($match==$last)
		continue;
	if($first)
		$first=false;
	else
		echo ",\n";
	echo "   $match\n    => \"\"";
	$last=$match;
}
echo "\n);\n";
