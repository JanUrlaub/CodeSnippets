# CodeSnippets

## Get date range
```php
$time_start = DateTime::createFromFormat("Y-m-d",$string_from_date);
$time_end   = DateTime::createFromFormat("Y-m-d",$string_to_date);
while($time_start<=$time_end){
   $time_start->add(DateInterval::createFromDateString("1 day"));
}
```
or 
```php
$period = new DatePeriod(
    new DateTime($string_from_date),
    new DateInterval('P1D'),
    new DateTime($string_to_date)
);
```
or formate date
```php
date_format(date_create_from_format("dmY",$strange_string_from_date),"Y-m-d")
```

## PHP Spreadsheet
```php
PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($string_from_date);
PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(date_create_from_format("H:i:s",$string_from_time));
$Spreadsheet->getActiveSheet()->getStyle("D2:D10")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_TIME3);
$Spreadsheet->getActiveSheet()->getStyle("H2:H10")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD2);
$Spreadsheet->getActiveSheet()->getColumnDimension("D")->setAutoSize(TRUE); 
$Spreadsheet->getActiveSheet()->getColumnDimension("H")->setAutoSize(TRUE); 
```

## Get a max_execution time on php cli
```php
function set_time_limit_cli($timeout_seconds){
    //prevent cli process for zombie mode
    $pid = pcntl_fork();
    if (-1 == $pid) {
        die('Failed! Unable to fork.');
    } elseif ($pid == 0) {
        for($i=0;$i<$timeout_seconds;$i++){
            sleep(1);
        }
        
        posix_kill(posix_getppid(), SIGKILL);
        // You can do any on-failure clean-up here.
        die('Failed! Process timed out and was killed.'."\n");
    }
}
```

## get GPS coordinates of an address 
```php
$geocoder = new \Geocoder\ProviderAggregator();
$adapter  = new \Http\Client\Curl\Client();
$geocoder->registerProviders ([new \Geocoder\Provider\GoogleMaps\GoogleMaps($adapter, '&key=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')]);
$result = $geocoder->using('google_maps')->geocodeQuery(\Geocoder\Query\GeocodeQuery::create($address));
if ($result->count()>0) {
    $data["lat"]=$result->first()->getCoordinates()->getLatitude();
    $data["long"]=$result->first()->getCoordinates()->getLongitude();
    $data["country"]=$result->first()->getCountry()->getCode();
    $data["federal_state"]=$result->first()->getAdminLevels()->first()->getName();
}
```

## use flysystem as handler fpor webvdav, local file system and any other file systems
```php
$client = new Sabre\DAV\Client($webdav_setting);
$client->addCurlSetting(CURLOPT_SSL_VERIFYPEER,false);
$folder = $encodePath($folder);
$webdavAdapter 	= new League\Flysystem\WebDAV\WebDAVAdapter($client,"remote.php/webdav/".$folder);
$flysystem = new League\Flysystem\Filesystem($webdavAdapter);
function encodePath($path)
{
    $a = explode('/', $path);
    for ($i=0; $i<count($a); $i++) {
        $a[$i] = rawurlencode($a[$i]);
    }
    return implode('/', $a);
}
```

## Microsoft EWS Support
get Folder and its mails
```php
$recipient = new \jamesiarmes\PhpEws\Type\EmailAddressType();
$recipient->EmailAdcdress = $mailAdress;
// Build the request.
$request = new FindItemType(); $request->ItemShape = new ItemResponseShapeType(); $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES; $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType(); $request->Traversal = ItemQueryTraversalType::SHALLOW;

// Search in the user's inbox.
$folder_id = new DistinguishedFolderIdType(); $folder_id->Id = DistinguishedFolderIdNameType::INBOX;
$folder_id->Mailbox = $recipient;
$request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;
$response = $client->FindItem($request);
$response_messages = $response->ResponseMessages->FindItemResponseMessage;
$inbox_folder    = $response_messages[0];
```
Find a folder in mailbox 
```php
private function get_folder($folder_name){

    // Build the request.
    $request = new FindFolderType();
    $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();
    $request->Restriction = new RestrictionType();

    // Search recursively.
    $request->Traversal = FolderQueryTraversalType::DEEP;

    // Search within the root folder. Combined with the traversal set above, this
    // should search through all folders in the user's mailbox.
    $parent = new DistinguishedFolderIdType();
    $parent->Id = DistinguishedFolderIdNameType::ROOT;
    $parent->Mailbox = $this->recipient;
    $request->ParentFolderIds->DistinguishedFolderId[] = $parent;

    // Build the restriction that will search for folders containing "Cal".
    $contains = new ContainsExpressionType();
    $contains->FieldURI = new PathToUnindexedFieldType();
    $contains->FieldURI->FieldURI = UnindexedFieldURIType::FOLDER_DISPLAY_NAME;
    $contains->Constant = new ConstantValueType();
    $contains->Constant->Value = $folder_name;
    $contains->ContainmentComparison = ContainmentComparisonType::EXACT;
    $contains->ContainmentMode = ContainmentModeType::SUBSTRING;
    $request->Restriction->Contains = $contains;

    $response = $this->client->FindFolder($request);

    // Iterate over the results, printing any error messages or folder names and
    // ids.
    $response_messages = $response->ResponseMessages->FindFolderResponseMessage;
    if(empty($response_messages)){
        return null;
    }
    if(empty($response_messages[0]->RootFolder->Folders->Folder)){
        return null;
    }

    return $response_messages[0]->RootFolder->Folders->Folder[0];

}
```
Get attachements of an folder
```php
public function get_mail_attachements_ews($cache_path, $message_id,$convertHtml=true){
    // Build the get item request.
    $request = new GetItemType();
    $request->ItemShape = new ItemResponseShapeType();
    $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
    $request->ItemShape->BodyType  = BodyTypeResponseType::HTML;
    $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();

    // Add the body property.
    $body_property = new PathToUnindexedFieldType();
    $body_property->FieldURI = 'item:Body';
    $request->ItemShape->AdditionalProperties = new NonEmptyArrayOfPathsToElementType();
    $request->ItemShape->AdditionalProperties->FieldURI = array($body_property);

    // Add the message id to the request.
    $item = new ItemIdType();
    $item->Id = $message_id;
    $request->ItemIds->ItemId[] = $item;

    $paths = array();


    $response = $this->client->GetItem($request);


    // Iterate over the results, printing any error messages or receiving
    // attachments.
    $response_messages = $response->ResponseMessages->GetItemResponseMessage;
    foreach ($response_messages as $response_message) {
        // Iterate over the messages, getting the attachments for each.
        $attachments = array();
        foreach ($response_message->Items->Message as $item) {
            // If there are no attachments for the item, move on to the next
            // message.
            if (empty($item->Attachments)) {
                continue;
            }

            // Iterate over the attachments for the message.
            foreach ($item->Attachments->FileAttachment as $attachment) {
                $attachments[] = $attachment->AttachmentId->Id;
            }
        }

        // Build the request to get the attachments.
        $request = new GetAttachmentType();
        $request->AttachmentIds = new NonEmptyArrayOfRequestAttachmentIdsType();

        // Iterate over the attachments for the message.
        foreach ($attachments as $attachment_id) {
            $id = new RequestAttachmentIdType();
            $id->Id = $attachment_id;
            $request->AttachmentIds->AttachmentId[] = $id;
        }

        $response = $this->client->GetAttachment($request);

        // saving the attachments.
        foreach ($response->ResponseMessages->GetAttachmentResponseMessage as $attachment_response_message) {
            $attachments = $attachment_response_message->Attachments->FileAttachment;
            foreach ($attachments as $attachment) {
                $isMultiPart = false;
                try{
                    $document    = new Riverline\MultiPartParser\Part($attachment->Content);
                    $isMultiPart = $document->isMultiPart();
                }catch (InvalidArgumentException $exceptio){
                    $isMultiPart = false;
                }
                if ($isMultiPart) {
                    $this->get_multipart( $document,$paths,$cache_path);
                }else{
                    $path["path"] = $cache_path . $attachment->Name;
                    $path["name"] = $attachment->Name;
                    $paths[]=$path;
                    file_put_contents( $path["path"], $attachment->Content);
                }
            }
        }
    }
}
```
do authetication check with given email and password 
```php
$ldap = new Laminas\Ldap\Ldap($options);
$adapter = new Laminas\Authentication\Adapter\Ldap($option, $email,$password);  
$result = $auth->authenticate($adapter); 
$status=$result->isValid();
$code = $result->getCode();
```
find an element by filter and return php friendly array
```php

$ldap->bind();
$result = $ldap->search(
    $filter,
    $dn,
    Laminas\Ldap\Ldap::SEARCH_SCOPE_SUB
);
if($ldap->getLastErrorCode()){
    die($ldap->getLastError());
}
$return = $result->toArray();

```
Update value 
```php
function ldapSetAttribute(string $changedn,array $attributs,array $entry=array())
{

    foreach ($attributs as $key=> $attribut)
    {
    Laminas\Ldap\Attribute::setAttribute($entry, $key, $attribut);
    }
    
    try {
        $this->ldap->update($changedn, $entry);
        $action=array(
            "status"    =>TRUE,
            "message"   =>"Erstellung POS"
        );
        
    } catch (Laminas\Ldap\Exception\LdapException $zle) {
        $action=array(
            "status"    =>FALSE,
            "message"   => $zle->getMessage()
        );
        log_message('error', "ldapSetAttribute ". $zle->getMessage());
    } 
    return  $action;         
}
```
Identify browser
```php
$browser = new Wolfcast\BrowserDetection();
if (
    ($browser->getName() == Wolfcast\BrowserDetection::BROWSER_SAFARI) 
    || (($browser->getPlatform()=="Android") &&($browser->getPlatformVersion(true)<=5))
    || (($browser->getName() == Wolfcast\BrowserDetection::BROWSER_EDGE) && ($browser->getVersion(true)<=79))
){
}
```
