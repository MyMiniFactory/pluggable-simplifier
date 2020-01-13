<?php

// Reading command arguments
$OPTIONS = getopt("f:i:o:s:t:c", ["filename:","input:","output:","status:","targetsize:","convert"]);

// Required params
$FILENAMEARG = (array_key_exists("f", $OPTIONS) ? $OPTIONS['f'] : $OPTIONS['filename']) ;
$INPUTARG = (array_key_exists("i", $OPTIONS) ? $OPTIONS['i'] : $OPTIONS['input']);
$OUTPUTARG = (array_key_exists("o", $OPTIONS) ? $OPTIONS['o'] : $OPTIONS['output']);
$STATUSARG = (array_key_exists("s", $OPTIONS) ? $OPTIONS['s'] : $OPTIONS['status']);
$TARGETSIZE = (array_key_exists("t", $OPTIONS) ? $OPTIONS['t'] : $OPTIONS['targetsize']);

// Optional params
$CONVERT = false;
if(array_key_exists("c", $OPTIONS) or array_key_exists("convert", $OPTIONS)){
  $CONVERT = true;
}


echo($FILENAMEARG . PHP_EOL);
echo($INPUTARG . PHP_EOL);
echo($OUTPUTARG . PHP_EOL);
echo($STATUSARG . PHP_EOL);
echo($TARGETSIZE . PHP_EOL);
echo($CONVERT . PHP_EOL);

// Creating folders
if(!is_dir($INPUTARG)) {
  mkdir($INPUTARG);
}
if(!is_dir($OUTPUTARG)) {
  mkdir($OUTPUTARG);
}


$filesInInput = array_slice(scandir($INPUTARG), 2);

$filesToProcess = [
    [
        "objectName" => $FILENAMEARG, 
        "objectPath" => $INPUTARG . '/' . $filesInInput[0]
    ]
];

$statusJson = [];
$fp = fopen($STATUSARG.'/status.json', 'w');
fwrite($fp, json_encode($statusJson));
fclose($fp);
$metadataJson = [];

// Processing each file
foreach ($filesToProcess as $file) {
  $time_start = microtime(true); 

    // Conversion to stl if its a obj
    if(file_exists($file["objectPath"])) {
        $file_extension = pathinfo($file["objectPath"], PATHINFO_EXTENSION);

        // Checking validity of file for simplification
        if(file_exists($file["objectPath"]) &&  strtolower($file_extension) != "stl"){
          echo(PHP_EOL."Converting file to stl".PHP_EOL);
            $stlPath = "/app/tmp/".$file["objectName"].".stl";
            exec("ctmconv ".$file["objectPath"]." ".$stlPath);
            if(file_exists($stlPath)){
              $file["objectPath"] = $stlPath;
              // copy($stlPath, $OUTPUTARG.'/'.$file["objectName"].'-converted.stl');
            } else {
              echo("Error unsuported file type for rendering");
              $fp = fopen('/app/files/results.json', 'w');
              fwrite($fp, json_encode($statusJson));
              fclose($fp);
              return 0;
            }
        }
    } else {
        echo("Error file".$file["objectName"]." not found");
        $fp = fopen('/app/files/results.json', 'w');
              fwrite($fp, json_encode($statusJson));
              fclose($fp);
              return 0;
    }

    echo($file["objectPath"]);

    // Stl simplification under threshold
    $targetSize = $TARGETSIZE * 1048576;
    $fileSize = filesize($file["objectPath"]);

    echo(PHP_EOL."old size : ".$fileSize.PHP_EOL);

    $path = "/app/tmp/".$file["objectName"]."-simplified.stl";
    $percentageDecrease = 1 - round(($fileSize - $targetSize)/$fileSize, 2, PHP_ROUND_HALF_DOWN);
    echo("Percentage to decrease: ".$percentageDecrease.PHP_EOL);
    exec("/app/a.out ".$file["objectPath"]." ".$path." ".$percentageDecrease);
    
    if (file_exists($path)){
      // copy($path, $OUTPUTARG.'/'.$file["objectName"].'-simplified.stl');
      echo("new size : ".filesize($path).PHP_EOL);
      array_push($statusJson, [
        "file simplification" => [
          "status" => "done",
          "progress" => "100%"
        ]
      ]);
      $fp = fopen($STATUSARG.'/status.json', 'w');
      fwrite($fp, json_encode($statusJson));
      fclose($fp);
    } else {
      echo("Error while simplifying file");
      array_push($statusJson, [
        "file simplification" => [
          "status" => "error",
          "progress" => "100%"
        ]
      ]);
      $fp = fopen($STATUSARG.'/status.json', 'w');
      fwrite($fp, json_encode($statusJson));
      fclose($fp);
      $fp = fopen('/app/files/results.json', 'w');
            fwrite($fp, json_encode($statusJson));
            fclose($fp);
      return 0;
    }

    // Conversion to PLY and copy to output
    $stlPath = "/app/tmp/".$file["objectName"]."-simplified.stl";
    if($CONVERT){
      echo("Conversion to ply");
      $plyPath = "/app/tmp/".$file["objectName"]."-simplified.ply";
      exec("ctmconv ".$stlPath." ".$plyPath);

      copy($plyPath, $OUTPUTARG.'/'.$file["objectName"].'.ply');
    } else {
      copy($stlPath, $OUTPUTARG.'/'.$file["objectName"].'.stl');
    }

      
  }

// Clearing the tmp folder recursively
$dir = 'tmp';
$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

foreach($files as $file) {

    if ($file->isDir()){
        rmdir($file->getRealPath());
    } else {
        unlink($file->getRealPath());
    }

}
$time_end = microtime(true);

$execution_time = ($time_end - $time_start)/60;

array_push($statusJson, [
  "processing" => [
    "status" => "done",
    "progress" => "100%",
    "execution_time" => $execution_time
  ]
]);

// Writting the status file
$fp = fopen($STATUSARG.'/status.json', 'w');
fwrite($fp, json_encode($statusJson));
fclose($fp);

return 0;

?>
