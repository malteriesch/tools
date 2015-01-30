aTestQueue          = new Array();
iCurrentQueueIndex  = 0;
iFailed             = 0;
bTestsStopped       = false;
bAnyFailed          = false;
iTotalPasses 		= 0;
iTotalFailures 		= 0;
iTotalTestsRun 		= 0;


var config = {
	"image_default"  : "resources/unittest/images/default.jpg",
	"image_pass"     : "resources/unittest/images/passed.jpg",
	"image_fail"     : "resources/unittest/images/failed.jpg",
	"ajax_url"       : "/unittest/ajax",
	"standalone_url" : "/unittest/standalone"
}

function toggleFailures( iTest) {
	$('#results'+iTest).toggleClass('hidden');
	$('#focus'+iTest).focus();
}

function toggle( iCheckId ) {

    if ( !IsSelected ( iCheckId )) {
    	selectTest( iCheckId );
    } else {
    	deselectTest( iCheckId );
    }


}

function selectTest( iCheckId ) {
	GetClearedRow( iCheckId ).addClass('selected');

}

function deselectTest( iCheckId ) {
	$('#status'+iCheckId).html('&nbsp;');
	$('#number_failures'+iCheckId).html( '&nbsp;' );
	$('#number_passes'+iCheckId).html( '&nbsp;' );
	GetClearedRow( iCheckId ).removeClass('&nbsp;')
}

function PerformSelectedTests() {
	ResetCounters();
	bAnyFailed          = false;
	document.getElementById('pic').src=config.image_default;
    bTestsStopped = false;
	aTestQueue       = new Array();
	iCurrentQueueIndex = 0;
    $("input.test").each( function( ) {
        iTestNumber = this.value;
        if (IsSelected(iTestNumber) ) {
    		aTestQueue[aTestQueue.length]=iTestNumber;
        }
    } );
	RunNextTest();
}


function selectByExpression() {
	var sRegEx = $('#select_expression').val();

	$("input.test").each( function( ) {
		var oRegEx = new RegExp( sRegEx , 'gi');
        iTestNumber = this.value;
        var sCurrentTestCase = $('#test'+iTestNumber).val();
        if (oRegEx.test( sCurrentTestCase )) {
        	selectTest( iTestNumber );
        }

    } );
}
function runByExpression() {
	var sRegEx = $('#select_expression').val();

	$("input.test").each( function( ) {
		var oRegEx = new RegExp( sRegEx , 'gi');
		iTestNumber = this.value;
		var sCurrentTestCase = $('#test'+iTestNumber).val();
		if (oRegEx.test( sCurrentTestCase )) {
			window.open( config.standalone_url+'?test='+sCurrentTestCase, 'Test_'+iTestNumber )
		}

	} );
}

function deselectByExpression() {
	var sRegEx = $('#select_expression').val();
	$("input.test").each( function( ) {
		var oRegEx = new RegExp( sRegEx , 'gi');
        iTestNumber = this.value;
        var sCurrentTestCase = $('#test'+iTestNumber).val();
        if (oRegEx.test( sCurrentTestCase )) {
        	deselectTest( iTestNumber );
        }
    } );
}

function ReRunPassedTests() {
	ResetCounters();
	bAnyFailed          = false;
    document.getElementById('pic').src=config.image_default;
    bTestsStopped = false;
	aTestQueue       = new Array();
	iCurrentQueueIndex = 0;
    $("input.test").each( function( ) {
        iTestNumber = this.value;
        if (IsPassed(iTestNumber)) {
    		aTestQueue[aTestQueue.length]=iTestNumber;
        }
    } );
	RunNextTest();
}

function ReRunFailedTests() {
	ResetCounters();
	bAnyFailed          = false;
    document.getElementById('pic').src=config.image_default;
    bTestsStopped = false;
	aTestQueue       = new Array();
	iCurrentQueueIndex = 0;
    $("input.test").each( function( ) {
        iTestNumber = this.value;
        if (IsFailed(iTestNumber)) {
    		aTestQueue[aTestQueue.length]=iTestNumber;
        }
    } );
	RunNextTest();
}
function ResetCounters() {
	 iTotalPasses 		= 0;
	 iTotalFailures 	= 0;
	 iTotalTestsRun 	= 0;
}
function ResetTests() {
	ResetCounters();
	bAnyFailed          = false;
	aTestQueue          = new Array();
    iCurrentQueueIndex  = 0;
    iFailed             = 0;
    bTestsStopped       = false;
    UpdateStats();
    $("input.test").each( function( ) {
        iTestNumber = this.value;
        deselectTest( iTestNumber );
    } );

    document.getElementById('pic').src=config.image_default;
}

function SelectAllTests() {
	ResetCounters();
	ResetTests();
    $("input.test").each( function( ) {
        iTestNumber = this.value;
        selectTest( iTestNumber );
    } );
}

function StopTests() {
	bTestsStopped = true;
}
function SetStatusMessage( iTest, sStatus ) {
	$('#status'+iTest).text( sStatus );
}


function RunNextTest() {
	if ( aTestQueue.length == 0 ) {
		FinishTests();
		return;
	}
	iTotalTestsRun++;

    var iCurrentTest = aTestQueue[ iCurrentQueueIndex ];
	GetClearedRow( iCurrentTest ).addClass(' running' );
	SetStatusMessage( iCurrentTest, 'running' );
	var sCurrentTestCase = $('#test'+iCurrentTest).val();
	$('#focus'+iCurrentTest).focus();

	$.ajax( {type: "GET"
		     , url: config.ajax_url+"?test="+sCurrentTestCase
		     , success: function (xml) {

							var oRow = GetClearedRow( iCurrentTest );
							var sState = $(xml).find('state').text();
							var sFailureDetails   = $(xml).find('failures').text();
							var iNumberOfFailures = $(xml).find('test_case_all_failures').text();
							var iNumberOfPasses   = $(xml).find('test_case_passes').text();
							var bEmptyTest        = iNumberOfFailures == 0 && iNumberOfPasses == 0;

							if ( iNumberOfFailures=='' ) {
								iNumberOfFailures = 0;
							}
							if ( iNumberOfPasses=='' ) {
								iNumberOfPasses = 0;
							}

							iTotalPasses   += parseInt( iNumberOfPasses );
							iTotalFailures += parseInt( iNumberOfFailures );

					    	if ( sState!= 'OK' || bEmptyTest  ) {
					    		bAnyFailed = true;
					    		oRow.addClass('failed');
					    	} else {
					    		oRow.addClass('passed');
					    	}

					    	if ( bEmptyTest ) {
					    		bAnyFailed = true;
					        	sState = 'Empty Test';
					        	$('#results'+iCurrentTest).html('<pre>This test is empty.</pre>');
					    	} else if (parseInt( iNumberOfFailures ) > 0 ) {
					    		$('#results'+iCurrentTest).html( "<pre>"+sFailureDetails+"</pre>"  );
					    	} else {
					    		$('#results'+iCurrentTest).html( "No Errors"  );
					    	}

					    	SetStatusMessage( iCurrentTest, sState );

					    	$('#number_failures'+iCurrentTest).html( iNumberOfFailures );
					    	$('#number_passes'+iCurrentTest).html( iNumberOfPasses );
					    	UpdateStats();

					    	iCurrentQueueIndex++;
					    	if (iCurrentQueueIndex< aTestQueue.length && !bTestsStopped ) {
					    		RunNextTest();
					    	} else {
					    		FinishTests();
					    	}
				}
	       , error: function() {
	    	   			bAnyFailed = true;
	    	   			var oRow = GetClearedRow( iCurrentTest );
	    	   			oRow.addClass('failed');
	    	   			SetStatusMessage( iCurrentTest, 'Error' );
	    	   			$('#results'+iCurrentTest).html('<pre>Fatal error, use "run stand alone" for further info</pre>');
	    	   			$('#number_failures'+iCurrentTest).html( '&nbsp;' );
	    	   			$('#number_passes'+iCurrentTest).html( '&nbsp;' );
	    	   			iCurrentQueueIndex++;
	    	   			iTotalFailures += 1;
				    	if (iCurrentQueueIndex< aTestQueue.length && !bTestsStopped ) {
				    		RunNextTest();
				    	} else {
				    		FinishTests();
				    	}
	    	   			UpdateStats();
	       } } );
}

function UpdateStats() {

	if (iTotalFailures == 0 ) {
		$('#total_failures').html( '0' );
	} else {
		$('#total_failures').html( iTotalFailures );
	}

	if (iTotalPasses == 0 ) {
		$('#total_passes').html( '0' );
	} else {
		$('#total_passes').html( iTotalPasses );
	}

	if (iTotalTestsRun == 0 ) {
		$('#total_tests').html( '0' );
	} else {
		$('#total_tests').html( iTotalTestsRun );
	}

}

function FinishTests() {
	if (aTestQueue.length == 0) {
		document.getElementById('pic').src=config.image_default;
		return;
	}
	if ( bAnyFailed ) {
		document.getElementById('pic').src=config.image_fail;
	} else {
		document.getElementById('pic').src=config.image_pass;
	}
}

function GetClearedRow( iTest ) {
	return GetRow( iTest )
	 .removeClass('passed')
	 .removeClass('selected')
	 .removeClass('failed')
	 .removeClass('running');
}

function GetRow( iTest ) {
    return $('#testrow'+iTest);
}

function IsSelected( iTest ){
    return GetRow( iTest ).hasClass('selected');
}

function IsPassed( iTest ){
	return GetRow( iTest ).hasClass('passed');
}

function IsFailed( iTest ){
	return GetRow( iTest ).hasClass('failed');
}


function syncDb() {
	$('#dbsync').html("Applying Patches....");
	$.get("/unittest/patches", function (result) {
		showDbResult("Patches applied.",result);
	} );
}

function reBuildDb() {
	$('#dbsync').html("Rebuilding Database....");
	$.get("/unittest/rebuild", function (result) {
		showDbResult("Database Rebuilt.",result);
	} );
}
function showDbResult(successMessage, result) {
	if (result) {
		$('#dbsync').html("<pre>"+result+"</pre>");
	} else {
		$('#dbsync').html(successMessage);
	}
}
