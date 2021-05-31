class SaignsRequestApi
{
	static execute(options)
	{
		var sUrl;
		var sDatasource;
		var sLoader;
		var sExecute = '';
		var sTimeout = 30000;
		var bClearForm;
		var sFocus = '';
		var hCallbackBefore;
		var hCallbackAfter;
		var hCallbackError;
		var hCallbackBeforeProcess;
		var hFormdata;
		var sFormID;
		var sMethod = 'POST';
		
		if ( typeof options.url !== 'undefined' ) sUrl = options.url;
		if ( typeof options.method !== 'undefined' ) sMethod = options.method;
		if ( typeof options.execute !== 'undefined' ) sExecute = options.execute;
		if ( typeof options.source !== 'undefined' ) sDatasource = options.source;
		if ( typeof options.loader !== 'undefined' ) sLoader = options.loader;
		if ( typeof options.timeout !== 'undefined' ) sTimeout = options.timeout;
		if ( typeof options.focus !== 'undefined' ) sFocus = options.focus;
		if ( typeof options.clearform !== 'undefined' ) bClearForm = options.clearform;
		if ( typeof options.before !== 'undefined' ) hCallbackBefore = options.before;
		if ( typeof options.beforeprocess !== 'undefined' ) hCallbackBeforeProcess = options.beforeprocess;
		if ( typeof options.after !== 'undefined' ) hCallbackAfter = options.after;
		if ( typeof options.error !== 'undefined' ) hCallbackError = options.error;
		
		
		if ( typeof sDatasource === 'string' )
		{
			console.log("SaignsRequestApi.execute(): sDatasource is string");
			hFormdata = new FormData($(sDatasource)[0]);
			hFormdata.append('requestapi_set','1');
			hFormdata.append('execute',sExecute);
			hFormdata.append('submitvalue',$("input[type=submit][clicked=true]").attr('submitvalue'));
			
			console.log('execute' + " -> " + sExecute);
			for( var pair of hFormdata.entries() ) {
				console.log(pair[0] + " -> " + pair[1]);
			}
			if ( sFocus != '' )
			{
				hFormdata.append('requestapi_focus',sFocus);
			}
		}
		else if ( typeof sDatasource === 'object' )
		{
			console.log("SaignsRequestApi.execute(): sDatasource is object");
			hFormdata = new FormData();
			hFormdata.append('requestapi_set','1');
			hFormdata.append('execute',sExecute);
			console.log('execute' + " -> " + sExecute);
			for (var key of Object.keys(sDatasource))
			{
				hFormdata.append(key,sDatasource[key]);
			    console.log(key + " -> " + sDatasource[key]);
			}
			
			if ( sFocus != '' )
			{
				hFormdata.append('requestapi_focus',sFocus);
			}
		}
		else
		{
			console.log("SaignsRequestApi.execute(): sDatasource is undefined");
			
			hFormdata = new FormData();
			hFormdata.append('requestapi_set','1');
			hFormdata.append('execute',sExecute);
			console.log('execute' + " -> " + sExecute);
		}
		
		if ( typeof hFormdata === 'undefined' )
		{
			console.log("SaignsRequestApi.execute(): hFormdata undefined");
			return;
		}
		
		if ( typeof bClearForm !== 'undefined' )
		{
			if ( typeof bClearForm === 'boolean' && bClearForm )
			{
				$(sDatasource).find("input[type=text], input[type=password], textarea").val("");
			}
			if ( Array.isArray(bClearForm) )
			{
				for ( var i = 0 ; i < bClearForm.length ; i++ )
				{
					$(bClearForm[i]).val("");
				} 
			}
		}
		
		$.ajax({
			type: sMethod,
			url: sUrl,
			timeout: sTimeout,
			data: hFormdata,
			processData: false,
			contentType: false,
			success: function ( resultData ) {
				if ( typeof hCallbackBeforeProcess == 'function' )
				{
					hCallbackBeforeProcess();
				}
			
				if ( resultData == '' )
				{
					console.log("resultData empty");
					return;
				}

				console.log(resultData);
				var jsonData = RequestApi.json_parse(resultData);
				if ( !jsonData )
				{
					if ( typeof hCallbackError === 'function' )
					{
						resultData = resultData.split("\n").join('<br>');
						hCallbackError(undefined,'json_parse() failed',resultData);
					}
					console.log("json_parse() failed");
					return;
				}
				
				if ( typeof jsonData.redirect !== 'undefined' && jsonData.redirect != '')
				{
					window.location.href = jsonData.redirect;
				}
				
				if ( typeof jsonData.reload !== 'undefined' && jsonData.reload == '1')
				{
					window.location.reload();
				}
				
				for ( var i = 0 ; i < jsonData.content.length ; i++ )
				{
					var id = jsonData.content[i].id;
					var type = jsonData.content[i].type;
					var sethtml = jsonData.content[i].sethtml;
					var html = jsonData.content[i].html;
					var call = jsonData.content[i].call;
					var callargs = jsonData.content[i].callargs;
					
					if ( !$(id).length && type != 'call' )
					{
						console.log("jsonData.content id '"+id+"' not found");
						continue;
					}
					
					if ( type == 'show' )
					{
						if ( sethtml == '1' )
						{
							$(id).html(html);
						}
						if ( $(id).is(':visible') )
						{
							$(id).hide();
						}
						$(id).show();
					}
					else if ( type == 'hide' )
					{
						$(id).hide();
						if ( sethtml == '1' )
						{
							$(id).html(html);
						}
					}
					else if ( type == 'html' )
					{
						$(id).html(html);
					}
					else if ( type == 'val' )
					{
						$(id).val(html);
					}
					else if ( type == 'call' )
					{
						if ( call == '' )
						{
							console.log("type is call, but call function is empty");
							continue;
						}
						if ( !Array.isArray(callargs) )
						{
							console.log("type is call, but callargs is not an array");
							continue;
						}
						if ( callargs.length > 5 )
						{
							console.log("type is call, but more callargs than 5 are not supported");
							continue;
						}
						
						if ( callargs.length == 0 ) window[call]();
						if ( callargs.length == 1 ) window[call](callargs[0]);
						if ( callargs.length == 2 ) window[call](callargs[0],callargs[1]);
						if ( callargs.length == 3 ) window[call](callargs[0],callargs[1],callargs[2]);
						if ( callargs.length == 4 ) window[call](callargs[0],callargs[1],callargs[2],callargs[3]);
						if ( callargs.length == 5 ) window[call](callargs[0],callargs[1],callargs[2],callargs[3],callargs[4]);
					}
					
					
				}
				
				if ( typeof jsonData.focus !== 'undefined' && jsonData.focus != '' )
				{
					$(jsonData.focus).focus();
				}
				
				if ( typeof jsonData.fsv !== 'undefined' && jsonData.fsv != '')
				{
				      $('input[name="fsv"]').each(function(){
				            $(this).val(jsonData.fsv);
				      })
				}
			},
			error:  hCallbackError,
			beforeSend: hCallbackBefore,
			complete: hCallbackAfter
		});	
	}	
	
	static json_parse(jsonString){
	    try {
	        var o = JSON.parse(jsonString);
	        if (o && typeof o === "object") {
	            return o;
	        }
	    }
	    catch (e) { }
	    return false;
	};

}