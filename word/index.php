<?php
function read_docx($filename)
{
    $content = '';

    if (!$filename || !file_exists($filename)) return false;

    $zip = zip_open($filename);
    if (!$zip || is_numeric($zip)) return false;

    while ($zip_entry = zip_read($zip)) {

        if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

        if (zip_entry_name($zip_entry) != "word/document.xml") continue;

        $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

        zip_entry_close($zip_entry);
    }
    zip_close($zip);

    $content = preg_replace('/<w:([A-Za-z0-9-]*)[^>]*?(\/?)>/', '<w:$1$2>', $content);
    $content = preg_replace('/<\/w:([A-Za-z0-9-]*)>/', "</w:$1>", $content);

    $table = array();
    preg_match_all('/<w:tr>(.*?)<\/w:tr>/', $content, $table);
    $table = $table[1];

    $i = 0;
    foreach ($table as $row) {
        preg_match_all('/<w:tc>(.*?)<\/w:tc>/', $row, $table[$i]);
        $table[$i] = $table[$i][1];
        $j = 0;
        foreach ($table[$i] as $col) {
            preg_match_all('/<w:r>(.*?)<\/w:r>/', $col, $table[$i][$j]);
            $table[$i][$j] = $table[$i][$j][1];
            $k = 0;
            foreach ($table[$i][$j] as $row_col) {
                preg_match_all('/<w:t>(.*?)<\/w:t>/', $row_col, $table[$i][$j][$k]);
                $table[$i][$j][$k] = implode($table[$i][$j][$k][1]);
                $k++;
            }
            $table[$i][$j] = implode("\n", $table[$i][$j]);
            $j++;
        }
        $i++;
    }
    echo '<pre>';
    print_r($table);
    echo '</pre>';

    return $table;
}

function read_doc($filename)
{
    if (file_exists($filename)) {
        if (($fh = fopen($filename, 'r')) !== false) {
            $headers = fread($fh, 0xA00);
            echo '<pre>';
            print_r($headers);
            echo '</pre>';
            // 1 = (ord(n)*1) ; Document has from 0 to 255 characters
            $n1 = (ord($headers[0x21C]) - 1);
            // 1 = ((ord(n)-8)*256) ; Document has from 256 to 63743 characters
            $n2 = ((ord($headers[0x21D]) - 8) * 256);
            // 1 = ((ord(n)*256)*256) ; Document has from 63744 to 16775423 characters
            $n3 = ((ord($headers[0x21E]) * 256) * 256);
            // 1 = (((ord(n)*256)*256)*256) ; Document has from 16775424 to 4294965504 characters
            $n4 = (((ord($headers[0x21F]) * 256) * 256) * 256);
            // Total length of text in the document
            $textLength = ($n1 + $n2 + $n3 + $n4);
            $extracted_plaintext = fread($fh, $textLength);
            if(!mb_detect_encoding($extracted_plaintext, 'UTF-8', true))
            {
                $extracted_plaintext = utf8_encode($extracted_plaintext);

            }
            // if you want to see your paragraphs in a new line, do this
            // return nl2br($extracted_plaintext);
            echo '<pre>';
            print_r($extracted_plaintext);
            echo '</pre>';

            return ($extracted_plaintext);
        } else {
            return false;
        }
    } else {
        return false;
    }
}

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Import file word</title>
</head>
<body>
<form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <button type="submit" name="submit">Upload</button>
</form>

<?php
if (isset($_POST['submit'])) {
    $filename = './files/' . $_FILES['file']['name'];
    move_uploaded_file($_FILES['file']['tmp_name'], $filename);
    if($_FILES['file']['type'] == "application/msword")
        read_doc($filename);
    else
        read_docx($filename);
}
?>

</body>
</html>