﻿/**
* General functionality of all feedtype-parsers.
*
* @author	Jeroen Wijering
* @version	1.1
**/


import com.jeroenwijering.utils.StringMagic;


class com.jeroenwijering.feeds.AbstractParser {

	
	/** Filter the feed for enclosures. **/
	private var enclosures:Boolean;
	/** All elements that can be parsed without manipulations **/
	private var elements:Object;
	/** Accepted mimetypes for enclosures **/
	private var mimetypes:Object;
	/** A prefix string for all files **/
	private var prefix:String;
	/** Timezone abbreviation offsets **/
	private var timezones:Object = { IDLW:-12,NT:-11,AHST:-10,CAT:-10,HST:-10,
		YST:-9,PST:-8,MST:-7,PDT:-7,CST:-6,EST:-5,EDT:-4,ADT:-3,WBT:-4,AST:-4,
		NT:-3.5,EBT:-3,AT:-2,WAT:-1,UTC:0,UT:0,GMT:0,WET:0,CET:1,CEST:1,EET:2,
		EEDT:3,MSK:3,IRT:3.5,SAMT:4,YEKT:5,TMT:5,TJT:5,OMST:6,NOVT:6,LKT:6,
		MMT:6.5,KRAT:7,ICT:7,WIT:7,WAST:7,IRKT:8,ULAT:8,CST:8,CIT:8,BNT:8,
		YAKT:9,JST:9,KST:9,EIT:9,ACST:9.5,VLAT:10,ACDT:10.5,SAKT:10,GST:10,
		MAGT:11,IDLE:12,PETT:12,NZST:12
	};


	/** Constructor. **/
	function AbstractParser(enc:Boolean,pre:String) {
		enclosures = enc;
		prefix = pre;
		setElements();
		setMimes();
	};


	/** build an array with all regular elements **/
	private function setElements() {
		elements = new Object();
	};


	/** build an array with all registered mimetypes **/
	private function setMimes() {
		mimetypes = new Object();
		mimetypes["mp3"] = "mp3";
		mimetypes["audio/mpeg"] = "mp3";
		mimetypes["flv"] = "flv";
		mimetypes["video/x-flv"] = "flv";
		mimetypes["jpeg"] = "jpg";
		mimetypes["jpg"] = "jpg";
		mimetypes["image/jpeg"] = "jpg";
		mimetypes["png"] = "png";
		mimetypes["image/png"] = "png";
		mimetypes["gif"] = "gif";
		mimetypes["image/gif"] = "gif";
		mimetypes["rtmp"] = "rtmp";
		mimetypes["application/x-shockwave-flash"] = "swf";
		mimetypes["rtmp"] = "rtmp";
		mimetypes["application/x-fcs"] = "rtmp";
	};


	/** Parse a specific object. **/
	function parse(xml:XML):Array {
		var arr = new Array();
		for(var i=0; i<xml.firstChild.childNodes.length; i++) {
			arr.push(xml.firstChild.childNodes[i].nodeName);
		}
		return arr;
	};


	/** Translate RFC2822 date strings to timestamp. **/
	private function rfc2Date(dat):Number {
		if(isNaN(dat)) {
			var darr:Array = dat.split(' ');
			darr[1] == "" ? darr.splice(1,1) : null;
			var month:Number = StringMagic.MONTH_INDEXES[darr[2]];
			var date:Number = darr[1].substring(0,2);
			var year:Number = darr[3];
			var zone = darr[5];
			var tarr = darr[4].split(':');
			var myDate = new Date(year,month,date,tarr[0],tarr[1],tarr[2]);
			var stamp = Math.round(myDate.valueOf()/1000) - 
				myDate.getTimezoneOffset()*60;
			if(isNaN(zone)) { 
				stamp -= 3600*timezones[zone]; 
			} else { 
				stamp -= 3600*Number(zone.substring(0,3)) - 
					60*Number(zone.substring(3,2));
			}
			var dat = new Date(stamp*1000);
			return stamp;
		} else {
			return dat;
		}
	};


	/** Translate ISO8601 date strings to timestamp. **/
	private function iso2Date(dat):Number {
		if(isNaN(dat)) {
			while(dat.indexOf(" ") > -1) {
				var idx = dat.indexOf(" ");
				dat = dat.substr(0,idx) + dat.substr(idx+1);
			}
			var myDate = new Date(dat.substr(0,4),dat.substr(5,2)-1,
				dat.substr(8,2),dat.substr(11,2),dat.substr(14,2),
				dat.substr(17,2));
			var stamp = Math.round(myDate.valueOf()/1000) - 
				myDate.getTimezoneOffset()*60;
			if(dat.length > 20) { 
				var hr:Number = Number(dat.substr(20,2));
				var mn:Number = Number(dat.substr(23,2));
				if(dat.charAt(19) == "-") {
					stamp = stamp - hr*3600 - mn*60;
				} else {
					stamp += hr*3600 + mn*60;
					trace(stamp);
				}
				var dat = new Date(stamp*1000);
			}
			return stamp;
		} else {
			return dat;
		}
	};


}