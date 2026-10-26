<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\DB;
use App\Configuration;

/** Get config **/
function getCfg( $name, $category , $type = 'config' )
{
	if($name!=''){
		$data = Configuration::where([ 'type' => $type , 'category' => $category , 'name' => $name , 'active' => 1 , 'company_id' => idCompany()])->first();
		//QUERY
		return $data->value;
	}else{
		$data = collect(Configuration::where([ 'type' => $type , 'category' => $category ,  'active' => 1 , 'company_id' => idCompany()])->select('name', 'value')->get()->toArray())->pluck('value', 'name');
		return $data;
		
	}
}

/** Compare two arrays **/
function compare2Arrays($array1_key,$array2)
{
	$updates=array();
	foreach ($array1_key as $key => $array1) {
		if ( $array1 != $array2[$key] ) {
			$updates[] = array($key,$array1,$array2[$key]);
		}
	}
	//Return array with key modified, old value and new value
	return $updates;
};

/** Display Compared Array in html table **/
function htmlComparedArray($array)
{
	$rows = '';
	foreach ($array as $modif) { if ($modif[0]!='') {
		$rows .= 	'<tr>
						<td class="text-center semi-bold">'.$modif[0].'</td>
						<td class="text-center">'.$modif[1].'</td>
						<td class="text-center">'.$modif[2].'</td>
					</tr>';
	} }
	return $rows;
}

/** Get User Item Auth **/
function authUser( $userItem ) {
	$user = Auth::user();
	if($user){

		if( is_array($userItem) ){
			foreach ($userItem as $key => $item) {
				$user = $user->{$item};
			}
		}else{
			$user = $user->{$userItem};
		}
	}
	return $user;
}

/** Storage a Upload Image **/
function storageImage( Request $request , $item, $filename, $disk , $resize = false , $resize_x = 0 , $resize_y  = 0)
{
	$file = $request->file( $item );
	safeUploadName($file->getClientOriginalName());
	$filename = basename(str_replace('\\', '/', $filename));
	setlocale(LC_ALL, "en_US.utf8");
	$filename = str_replace (["'", " "],["", "_"] , iconv('utf-8', 'ascii//TRANSLIT', ( ($filename == '') ? time() : $filename ) . '.' . $file->getClientOriginalExtension()));

	if( $resize )
	{
		$make = Image::decode( $file)->resize( $resize_x , $resize_y )->save( public_path( $disk . $filename ) ) ;
	}
	else
	{
		$make = Image::decode( $file)->save( public_path( $disk . $filename ) ) ;
	}
	return $filename;
}

/** Store each attachment without overwriting an earlier upload. */
function storageFile(Request $request, $item = false, $rename = false, $disk = 'files', $public = false)
{
    $files = $item ? $request->file($item) : $request->file();
    if ($rename) {
        $rename = basename(str_replace('\\', '/', $rename));
    }
    $directory = $public ? public_path($disk) : storage_path(config('app.path_storage')).$disk;
    $names = [];
    foreach (is_array($files) ? $files : [$files] as $file) {
        safeUploadName($file->getClientOriginalName());
        $name = $rename
            ? $rename.'_'.date('YmdHis').'.'.$file->getClientOriginalExtension()
            : date('YmdHis').'_'.$file->getClientOriginalName();
        $names[] = ['file' => storeUploadedFile($file, $directory, $name)];
    }
    return is_array($files) ? $names : $names[0]['file'];
}

/** Exclusive creation also protects simultaneous uploads with the same name. */
function storeUploadedFile($file, $directory, $name)
{
    safeUploadName($name);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new \RuntimeException('No se pudo crear el directorio de archivos.');
    }
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    $stem = pathinfo($name, PATHINFO_FILENAME);
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $candidate = $attempt === 0 ? $name : $stem.'_'.bin2hex(random_bytes(8)).($extension ? '.'.$extension : '');
        $path = rtrim($directory, '/').'/'.$candidate;
        $output = @fopen($path, 'xb');
        if (!$output) {
            if (file_exists($path) || is_link($path)) continue;
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }
        $input = null;
        try {
            $input = fopen($file->getRealPath(), 'rb');
            if (!$input || stream_copy_to_stream($input, $output) !== $file->getSize() || !fflush($output)) {
                throw new \RuntimeException('No se pudo guardar el archivo completo.');
            }
            chmod($path, 0644);
        } catch (\Throwable $error) {
            unlink($path);
            throw $error;
        } finally {
            if (is_resource($input)) fclose($input);
            fclose($output);
        }
        return $candidate;
    }
    throw new \RuntimeException('No se pudo crear un nombre de archivo único.');
}

/** Keep uploaded filenames out of executable paths. */
function safeUploadName($name, $checkType = true)
{
	$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
	if ($name !== basename(str_replace('\\', '/', $name)) || str_contains($name, "\0")
		|| str_starts_with($name, '.')
		|| ($checkType && preg_match('/^(php[0-9]*|phtml|pht|phar|html?|xhtml|shtml|svg|js|mjs|cgi|pl)$/i', $extension))) {
		throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Tipo o nombre de archivo no permitido.']);
	}
	return $name;
}

/** Resolve only existing files contained in the public upload directory. */
function publicUploadPath($relative)
{
	$root = realpath(public_path('uploads'));
	$path = realpath(public_path(ltrim($relative, '/')));
	if (!$root || !$path || !is_file($path) || !str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
		return null;
	}
	return $path;
}


/** Print data with format **/
function debugger( $data , $break = true)
{
	echo '<pre>';
	print_r($data);
	echo '</pre>';
	if($break){
		die();
	}
}

/** Dir Files **/
function include_dir_files($dir)
{
	foreach (glob( $dir . "/*.php") as $filename)
	{
		include $filename;
	}
}

function key_value($item, $key)
{
    return "$key. $item";
}

function sendNoticationError($e)
{	
	if (app()->environment('testing')) {
		return;
	}
	if(config('app.env') == 'production')
	{
		\Illuminate\Support\Facades\Log::channel('slack')->critical($e->getMessage(). "\r\n" . $e->getFile() . "\r\n" . $e->getLine() );
	}else{
		// Log it instead of dumping it into the response: callers still need to return their error.
		\Illuminate\Support\Facades\Log::error($e->getMessage(), ['exception' => $e]);
	}
}


function binaryToFile( $file, $path_upload ){
	$data = $file;
	list($type, $data) = explode(';', $data);
	list(, $data)      = explode(',', $data);
	$data = base64_decode($data);
	file_put_contents(public_path( $path_upload ), $data);   
	return (file_exists(public_path( $path_upload)) ) ? $path_upload : null;
}


function csvToArray($filename = '', $delimiter = ',')
{
    if (!file_exists($filename) || !is_readable($filename))
        return false;

    $header = null;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== false)
    {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
        {
            if (!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }

    return $data;
}

function check_in_range($start_date, $end_date, $date_from_user)
{
  // Convert to timestamp
  $start_ts = strtotime($start_date);
  $end_ts = strtotime($end_date);
  $user_ts = strtotime($date_from_user);

 // debugger($start_ts);
  // Check that user date is between start & end
  return (($user_ts >= $start_ts) && ($user_ts <= $end_ts));
}

function get_months_in_range($dateIni, $dateFin) {

	
	// Get year and month of initial date (From)
	$yearIni = date("Y", strtotime($dateIni));
	$monthIni = date("m", strtotime($dateIni));
	
	// Get year an month of finish date (To)
	$yearFin = date("Y", strtotime($dateFin));
	$monthFin = date("m", strtotime($dateFin));
	
	// Checking if both dates are some year
	
	if ($yearIni == $yearFin) {
	   $numberOfMonths = ($monthFin-$monthIni) + 1;
	} else {
	   $numberOfMonths = ((($yearFin - $yearIni) * 12) - $monthIni) + 1 + $monthFin;
	}
	return $numberOfMonths;
}

function getContactsFilter($projects)
{
	$donorAllies = null;
		foreach($projects as $key => $item){
			if (isset($item->donors) && !empty(($item->donors))) {
				if (empty($donorAllies)) {
					$donorAllies = $item->donors;
				} else {
					$donorAllies = $donorAllies.','.$item->donors;
				}
			}
			if (isset($item->allies) && !empty(($item->allies))) {
				if (empty($donorAllies)) {
					$donorAllies = $item->allies;
				} else {
					$donorAllies = $donorAllies.','.$item->allies;
				}
			}
		}
	return $donorAllies;
}

function calculatedRetainAmount($amount, $fee, $percentageRetain)
{
	$amountWithFee = round(($amount * $fee) / 100, 2);
	$availableAfterFee = ($amount - $amountWithFee);
	$operatingCostAmount = round(($availableAfterFee / ($percentageRetain + 100)) * $percentageRetain, 2);
	return $operatingCostAmount;
}
