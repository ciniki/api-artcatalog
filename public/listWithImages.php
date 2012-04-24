<?php
//
// Description
// ===========
// This method will list the art catalog pieces sorted by category.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_artcatalog_listWithImages($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No status specified'), 
        'section'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No section specified'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No limit specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/artcatalog/private/checkAccess.php');
    $rc = ciniki_artcatalog_checkAccess($ciniki, $args['business_id'], 'ciniki.artcatalog.notesList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');


	$strsql = "SELECT ciniki_artcatalog.id, image_id, name, year, media, catalog_number, size, framed_size, price, flags, location, notes, "
		. "";
	if( !isset($args['section']) || $args['section'] == 'category' ) {
		$strsql .= "IF(ciniki_artcatalog.category='', '', ciniki_artcatalog.category) AS sname ";
	} elseif( $args['section'] == 'media' ) {
		$strsql .= "IF(ciniki_artcatalog.media='', '', ciniki_artcatalog.media) AS sname ";
	} elseif( $args['section'] == 'location' ) {
		$strsql .= "IF(ciniki_artcatalog.location='', '', ciniki_artcatalog.location) AS sname ";
	} elseif( $args['section'] == 'year' ) {
		$strsql .= "IF(ciniki_artcatalog.year='', '', ciniki_artcatalog.year) AS sname ";
	}
	$strsql .= "FROM ciniki_artcatalog "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY sname COLLATE latin1_general_cs, name "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'artcatalog', array(
		array('container'=>'sections', 'fname'=>'sname', 'name'=>'section',
			'fields'=>array('sname')),
		array('container'=>'pieces', 'fname'=>'id', 'name'=>'piece',
			'fields'=>array('id', 'name', 'image_id', 'year', 'media', 'catalog_number', 'size', 'framed_size', 'price', 'flags', 'location', 'notes')),
		));
	// error_log($strsql);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sections']) ) {
		return array('stat'=>'ok', 'sections'=>array());
	}

	//
	// Add thumbnail information into list
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
	$sections = $rc['sections'];
	foreach($sections as $section_num => $section) {
		foreach($section['section']['pieces'] as $piece_num => $piece) {
			$rc = ciniki_images_loadCacheThumbnail($ciniki, $piece['piece']['image_id'], 75);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$sections[$section_num]['section']['pieces'][$piece_num]['piece']['image'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
		}
	}

	return array('stat'=>'ok', 'sections'=>$sections);
}
?>