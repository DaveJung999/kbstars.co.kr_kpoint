
<?php
function json_encode($data){
	switch(gettype($data))	{
		case 'boolean':
			return $data ? 'true' : 'false';
		case 'integer':
		case 'double':
			return $data;
		case 'string':
			return '"'	. strtr($data, array('\\' =>	'\\\\', '"' =>	'\\"')) . '"';
		case 'object':
			$data = get_object_vars($data);
			return $data;
		case 'array':
			$rel = FALSE; // relative array?
			$key = array_keys($data);
			
			foreach($key as $v)	{
				if(!is_int($v))	{
					$rel = TRUE;
					break;
				}
			}
			$arr = array();
			foreach($data as $k =>	$v)	{
				$arr[] = ($rel ? '"'	. strtr($k, array('\\' =>	'\\\\', '"' =>	'\\"')) . '":' : '') . json_encode($v);
			}
			return $rel ? '{'	. join(',', $arr) . '}' : '['	. join(',', $arr) . ']';
		default:
			return '""';
	}
}
//editor_upload.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES) && $_FILES['upload']['size'] > 0){
	$path = $_POST['path']?$_POST['path']:$_GET['path'];
	$uploadsrc = '/upload/image/'.$path."/";
	$uploadpath = $_SERVER['DOCUMENT_ROOT'].$uploadsrc;

	if (!is_dir($uploadpath)){
		mkdir($uploadpath, 0777);
		chmod($uploadpath, 0777);
	}

	$filename = $_FILES['upload']['name'];
	$tmpname = $_FILES['upload']['tmp_name'];

	$ext = strtolower(substr(strrchr($filename,'.'), 1));
	$filename = strpos(rawurlencode($filename),'%') !== false ? (microtime(true)*10000).'.'.$ext : $filename;

	$savefilename = $uploadpath.$filename;
	$uploaded_file = '';

	if ($ext == 'jpg' || $ext == 'gif' || $ext == 'png'){
		$image_info = getimagesize($tmpname);
		
		if ($image_info['mime'] == 'image/png' || $image_info['mime'] == 'image/jpeg' || $image_info['mime'] == 'image/gif'){
			if (move_uploaded_file($tmpname, iconv('utf-8', 'cp949', $savefilename))){
				$uploaded_file = $filename;
			}
		} else {
			echo json_encode(array(
				'uploaded' => '0',
				'error' => array('message' => '이미지 파일의 형식이 올바르지 않습니다.')
			));
			exit;
		}
	} else {
		echo json_encode(array(
			'uploaded' => '0',
			'error' => array('message' => 'jpg, gif, png 파일만 업로드가 가능합니다.')
		));
		exit;
	}
} else {
	echo json_encode(array(
		'uploaded' => '0',
		'error' => array('message' => '업로드중 문제가 발생하였습니다.')
	));
	exit;
}

echo json_encode(array(
	'uploaded' => '1',
	'fileName' => $uploaded_file,
	'url' => $uploadsrc.$uploaded_file
));
exit; ?>
