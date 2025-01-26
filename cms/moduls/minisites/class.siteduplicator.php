<?php
class siteduplicator {

    public $siteID;
    public $domainID;
    public $langID;
    public $overridegals;
    public $fromDomain;
    // clone sites data from bizOnline domainId=1 to other specific  domainID
    public $newPictures;

    public function __construct($siteID = -1,$domainID = -1,$langID = 1,$overridegals = false , $fromDomain = 1 , $cloneGalleries = false){
        $this->domainID = $domainID;
        $this->langID = $langID;
        $this->siteID = $siteID;
        $this->overridegals = $overridegals;
        $this->fromDomain = $fromDomain;
        $this->cloneGalleries = $cloneGalleries;
    }

    public function init(){
        $self = $this;
        if( $this->domainID == -1) {
            return false;
        }
        $this->cloneSitesDomains();
        $this->cloneSitesRooms();
        $this->cloneSitesUnits();
        if($this->cloneGalleries == true) {
            $self->cloneSitesGalleries();
            $self->cloneRoomGalleries();
            $self->cloneMainGalleries();


        }

    }

    public function cloneSitesDomains_temp(){
        $self = $this;
        if( $this->domainID == -1) {
            return false;
        }
        $sql = "select siteID,phone,phone2,phoneSms,whatsappPhone,maskyooPhone from sites_domains where siteID=".$self->siteID." and domainID=".$self->fromDomain;
        $site = udb::single_row($sql);
        $site['domainID'] = $self->domainID;
        if($exists = udb::single_value("select siteID from sites_domains where siteID=".$self->siteID." and domainID=".$self->domainID)){
            $site['lastUpdate'] = date("Y-m-d H:i:s");
            udb::update("sites_domains",$site," siteID=".$self->siteID." and domainID=".$self->domainID);
        }
        else {
            udb::insert("sites_domains",$site);
        }
        $self->cloneSitesLangs();
    }
    public function cloneSitesDomains(){
        $self = $this;
        if( $this->domainID == -1) {
            return false;
        }
        $sql = "select siteID,phone,phone2,phoneSms,whatsappPhone from sites_domains where siteID=".$self->siteID." and domainID=".$self->fromDomain;
        $site = udb::single_row($sql);
        $site['domainID'] = $self->domainID;
        if($exists = udb::single_value("select siteID from sites_domains where siteID=".$self->siteID." and domainID=".$self->domainID)){
            $site['lastUpdate'] = date("Y-m-d H:i:s");
            udb::update("sites_domains",$site," siteID=".$self->siteID." and domainID=".$self->domainID);
        }
        else {
            udb::insert("sites_domains",$site);
        }
        $self->cloneSitesLangs();
    }
    public function cloneSitesLangs(){
        $self = $this;
        if( $this->domainID == -1) {
            return false;
        }
        $sql = "select siteID,owners,siteName,langID from sites_langs where siteID=".$self->siteID." and domainID=".$this->fromDomain." and langID=1";
        $site = udb::single_row($sql);
        $site['domainID'] = $self->domainID;
        if($exists = udb::single_value("select siteID from sites_langs where langID=".$self->langID." and siteID=".$self->siteID." and domainID=".$self->domainID)){
            udb::update("sites_langs",$site," siteID=".$self->siteID." and domainID=".$self->domainID . " and langID=".$self->langID);
        }
        else {
            udb::insert("sites_langs",$site);
        }
        $self->cloneSitesRooms();

        $self->cloneSitesTranslation();
        $self->cloneAlias();
    }
    public function cloneSitesTranslation(){
        $self = $this;
        if( $this->domainID == -1) {
            return false;
        }
        $site = Translation::sites($self->siteID, '*', 1, $self->fromDomain);
        Translation::save_row('sites', $self->siteID, $site, $self->langID, $self->domainID);
    }
    public function cloneSitesGalleries(){
        $self = $this;
        if( $this->domainID == -1) {
            return false;
        }
        $Galleries = udb::single_column("select sites_galleries.galleryID from galleries left join sites_galleries using(galleryID) where galleries.domainID=".$self->fromDomain." and sites_galleries.siteID=".$self->siteID);
        if($self->overridegals == true) { //if override del all sites_galleries
            $oldGals = udb::full_list("select sites_galleries.galleryID from galleries left join sites_galleries using(galleryID) where galleries.domainID=".$self->domainID." and sites_galleries.siteID=".$self->siteID);
            foreach ($oldGals as $oldGal) {
                udb::query("delete from sites_galleries where galleryID=".$oldGal['galleryID']);
                udb::query("delete from galleries where galleryID=".$oldGal['galleryID']);
            }

        }
        foreach ($Galleries as $gal) {
            $self->dupGallery($gal , null , $self->domainID , $self->fromDomain);
        }
    }
    public function cloneSitesRooms(){
        $self = $this;
        if( $this->domainID == -1) {
            return false;
        }
        $toDomain = $this->domainID;
        $sql = "SELECT rooms_domains.* FROM `rooms_domains` left join rooms using (roomID) WHERE rooms_domains.`domainID` = ".$self->fromDomain." and rooms.siteID=" . $this->siteID;
        $rooms = udb::full_list($sql);
        foreach ($rooms as &$room) {
            if(!$exist = udb::single_row("select rooms_domains.* from rooms_domains left join rooms using (roomID) where rooms_domains.domainID=".$toDomain." and rooms.roomID=".$room['roomID'])) {
                $room['domainID'] = $toDomain;
                udb::insert("rooms_domains",$room);
            }
    else {
        udb::query("update rooms_domains set active=".$room['active']." where domainID=".$toDomain." and roomID=".$room['roomID']);
    }
    }

    $sql = "SELECT * FROM `rooms_langs` WHERE `domainID` = ".$self->fromDomain." AND `langID` = 1";
    $rooms = udb::full_list($sql);
    foreach ($rooms as &$room) {
        if(!$exist = udb::single_row("select * from rooms_langs where domainID=".$toDomain." and langID=1 and roomID=".$room['roomID'])) {
            $room['domainID'] = $toDomain;
            udb::insert("rooms_langs",$room);
        }
        else {
            $room['domainID'] = $toDomain;
            udb::update("rooms_langs",$room," domainID=".$toDomain." and langID=1 and roomID=".$room['roomID']);
        }
    }
    }
    public function cloneSitesUnits(){ //no units per domain
        //rooms_units
    }
    public function cloneMainGalleries(){
        //site_main_galleries
        $self = $this;
        if( $this->domainID == -1) {
            return false;
        }

        if($self->overridegals){
            $sql = "select * from site_main_galleries where site_main_galleries.siteID=".$self->siteID." and site_main_galleries.domainID=".$self->domainID;
            $galleries = udb::full_list($sql);
            udb::query("delete from site_main_galleries where site_main_galleries.siteID=".$self->siteID." and site_main_galleries.domainID=".$self->domainID);
            foreach ($galleries as $gallery) {
             udb::query("delete from galleries where galleryID=".$gallery['galleryID']);
            }
        }
        $sql = "select * from site_main_galleries where site_main_galleries.siteID=".$self->siteID." and site_main_galleries.domainID in (0,".$self->fromDomain.") order by domainID DESC";
        $galleries = udb::full_list($sql);

        foreach ($galleries as $gallery) {

            $self->dupGallery($gallery['galleryID'] , null , $self->domainID , $self->fromDomain , "site_main_galleries");
        }
    }
    public function cloneRoomGalleries(){
            $self = $this;
            if( $this->domainID == -1) {
                return false;
            }
        if($self->overridegals){
            $sql = "SELECT rooms_galleries.* FROM `rooms_galleries` left join galleries using(galleryID) left join rooms_domains on(rooms_domains.roomID = rooms_galleries.roomID) left join rooms on (rooms.roomID=rooms_galleries.roomID) where galleries.domainID=".$self->domainID." and  rooms_domains.domainID=".$self->domainID." and rooms.siteID=".$self->siteID;
            $galleries = udb::full_list($sql);

            foreach ($galleries as $gallery) {
                udb::query("delete from rooms_galleries where galleryID=".$gallery['galleryID']);
                udb::query("delete from galleries where galleryID=".$gallery['galleryID']);
            }
        }
            $sql = "SELECT rooms_galleries.* FROM `rooms_galleries` left join galleries using(galleryID) left join rooms_domains on(rooms_domains.roomID = rooms_galleries.roomID) left join rooms on (rooms.roomID=rooms_galleries.roomID) where galleries.domainID=".$self->fromDomain." and  rooms_domains.domainID=".$self->fromDomain." and rooms.siteID=".$self->siteID;
            $roomGalleries = udb::full_list($sql);
            foreach ($roomGalleries as $gal) {
                if($exist = udb::single_row("SELECT * FROM `rooms_galleries` left join galleries using(galleryID) where galleries.domainID=".$self->domainID." and rooms_galleries.roomID=".$gal['roomID'] . " LIMIT 1")) {
                            //print_r($exist);
                        }
                else {
                    $self->dupGallery($gal['galleryID'] , $gal['roomID'] , $self->domainID , $self->fromDomain ,null);
                    //echo $gal['galleryID'] , $gal['roomID'] , -1 , 1 ,null;
                    //echo '<BR><BR>';
                }
                }

        }

    public function cloneAlias() {
        $self = $this;
        if( $self->domainID == -1) {
            return false;
        }
        $sql = "select * from alias_text where `table`='sites' and ref=".$self->siteID." and langID=1 and domainID=".$self->fromDomain;
        $alias = udb::single_row($sql);
        unset($alias['id']);
        $alias['domainID'] = $self->domainID;
        if($exists = udb::single_value("select id from alias_text where `table`='sites' and ref=".$self->siteID." and langID=1 and domainID=".$self->domainID)){
            //udb::update("alias_text",$alias," id=".$exists);
        }
        else {
            //udb::insert("alias_text",$alias);
        }
    }

    public function dupGallery($galID , $roomID , $toDomain , $curDomain ,$galleryType = null){
        $self = $this;
        $useSiteID = $self->siteID;
        $copyTo = [];
        if($toDomain == -1){
            foreach(DomainList::get() as $dom) {
                if($dom['domainID'] != $curDomain)
                    $copyTo[] = $dom['domainID'];
            }
        }else{
            $copyTo[] = $toDomain;
        }

        if($galID){
            if($roomID){
                $que = "SELECT * FROM `galleries` left join rooms_galleries using (galleryID) WHERE galleryID=".$galID;
            }
            else {
                $que = "SELECT * FROM `galleries` left join sites_galleries using (galleryID) WHERE galleryID=".$galID;
            }
            $galleries = udb::single_row($que);
            $que = "SELECT pictures.*,pictures_text.pictureTitle,pictures_text.pictureDesc,pictures_text.pictureLink FROM `pictures` left join pictures_text on (pictures_text.pictureID = pictures.pictureID and pictures_text.langID=1) WHERE galleryID=".$galID;
            $pictures = udb::key_row($que,"fileID");
            foreach($copyTo as $domID){
                $newGal = [
                    "siteID" => $galleries['siteID'],
                    "pageID" => $galleries['pageID'],
                    "domainID" => $domID,
                    "galleryTitle" => $galleries['galleryTitle']
                ];
                $newgalID = udb::insert("galleries",$newGal);
                $this->newPictures = [];
                foreach($pictures as $picture){
                    $this->newPictures[$picture['fileID']] = ['galleryID' => $newgalID, 'fileID' => $picture['fileID'], 'showOrder' => $picture['showOrder'], 'summer' => $picture['summer'], 'winter' => $picture['winter']];
                    udb::insert("pictures",['galleryID' => $newgalID, 'fileID' => $picture['fileID'], 'showOrder' => $picture['showOrder'], 'summer' => $picture['summer'], 'winter' => $picture['winter']]);

                }
                $que2 = "SELECT * from pictures WHERE galleryID=".$newgalID;
                $pictures2 = udb::key_row($que2,"fileID");
                foreach($pictures2 as $picture){
                    udb::insert("pictures_text",['pictureID' => $picture['pictureID'],'pictureTitle'=>$pictures[$picture['fileID']]['pictureTitle'],'pictureDesc'=>$pictures[$picture['fileID']]['pictureDesc'],'pictureLink'=>$pictures[$picture['fileID']]['pictureLink'],'langID'=>1]);
                }
                if($roomID){
                    udb::insert('rooms_galleries', ['roomID'  => $roomID,'galleryID' =>  $newgalID]);
                }else{
                    if($galleryType){
                        udb::insert($galleryType, ['siteID'  => $useSiteID ,'galleryID' =>  $newgalID, 'domainID'=>$domID],true);
                    }
                    else {
                        udb::insert('sites_galleries', ['siteID'  => $galleries['siteID'],'galleryID' =>  $newgalID , 'showOrder' => $galleries['showOrder']],true);
                    }
                }
            }
        }
    }

}