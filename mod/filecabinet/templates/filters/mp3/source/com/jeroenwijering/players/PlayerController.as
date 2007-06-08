﻿/**
* User input management of the players MCV pattern.
*
* @author	Jeroen Wijering
* @version	1.7
**/


import com.jeroenwijering.players.AbstractController;


class com.jeroenwijering.players.PlayerController extends AbstractController {


	/** use SharedObject to save current file, item and volume **/
	private var playerSO:SharedObject;


	/** Constructor, save arrays and set currentItem. **/
	function PlayerController(cfg:Object,fed:Object) {
		super(cfg,fed);
		playerSO = SharedObject.getLocal("com.jeroenwijerin.players", "/");
		if(playerSO.data.volume != undefined && _root.volume == undefined) {
			config["volume"] = playerSO.data.volume;
		}
		if(playerSO.data.usecaptions != undefined && 
			_root.usecaptions == undefined) {
			config["usecaptions"] = playerSO.data.usecaptions;
		}
		if(playerSO.data.useaudio != undefined && 
			_root.useaudio == undefined) {
			config["useaudio"] = playerSO.data.useaudio;
		}
	};


	/** Complete the build of the MCV cycle and start flow of events. **/
	public function startMCV(mar:Array) {
		registeredModels = mar;
		sendChange("item",currentItem);
		sendChange("volume",config["volume"]);
		if(config["usecaptions"] == "false") { 
			config["clip"].captions._visible = false;
			config["clip"].controlbar.cc.icn._alpha = 40;
		}
		if(config["useaudio"] == "false") {
			config["clip"].audio.setStop();
			config["clip"].controlbar.au.icn._alpha = 40;
		}
		if(config["autostart"] == "false") {
			sendChange("pause",0);
			isPlaying = false;
		} else { 
			sendChange("start",0);
			isPlaying = true;
		}
	};


	/** PlayPause switch **/
	private function setPlaypause() {
		if(isPlaying == true) {
			isPlaying = false;
			sendChange("pause");
		} else { 
			isPlaying = true;
			sendChange("start");
		}
	};


	/** Play previous item. **/
	private function setPrev() {
		if(currentItem == 0) { var i:Number = feeder.feed.length - 1; }
		else { var i:Number = currentItem-1; }
		setPlayitem(i);
	};


	/** Play next item. **/
	private function setNext() {
		if(currentItem == feeder.feed.length - 1) { var i:Number = 0; } 
		else { var i:Number = currentItem+1; }
		setPlayitem(i);
	};


	/** Stop and clear item. **/
	private function setStop() { 
		sendChange("pause",0);
		sendChange("stop");
		sendChange("item",currentItem);
		isPlaying = false;
	};


	/** Forward scrub number to model. **/
	private function setScrub(prm) {
		isPlaying == true ? sendChange("start",prm): sendChange("pause",prm);
	};


	/** Play a new item. **/
	private function setPlayitem(itm:Number) {
		if(itm != currentItem) {
			itm > feeder.feed.length-1 ? itm = feeder.feed.length-1: null;
			if(feeder.feed[currentItem]['file'] != feeder.feed[itm]['file']) {
				sendChange("stop");
			}
			currentItem = itm;
			sendChange("item",itm);
		}
		if(feeder.feed[itm]["start"] == undefined) {
			sendChange("start",0);
		} else {
			sendChange("start",feeder.feed[itm]["start"]);
		}
		currentURL = feeder.feed[itm]['file'];
		isPlaying = true;
	};


	/** Get url from an item if link exists, else playpause. **/
	private function setGetlink(idx:Number) {
		if(feeder.feed[idx]["link"] == undefined) {
			setPlaypause();
		} else {
			getURL(feeder.feed[idx]["link"],config["linktarget"]);
		}
	};


	/** Determine what to do if an item is completed. **/
	private function setComplete() { 
		itemsPlayed++;
		if(feeder.feed[currentItem]["category"] == "commercial") {
			setNext();
		} else if(config["repeat"] == "false" || (config["repeat"] == "list"
		 	&& itemsPlayed == feeder.feed.length)) {
			sendChange("pause",0);
			isPlaying = false;
			itemsPlayed = 0;
		} else {
			if(config["shuffle"] == "true") {
				var i:Number = randomizer.pick();
			} else if(currentItem == feeder.feed.length - 1) {
				var i:Number = 0;
			} else { 
				var i:Number = currentItem+1;
			}
			setPlayitem(i);
		}
	};


	/** Check volume percentage and forward to models. **/
	private function setVolume(prm) {
		if (prm < 0 ) { prm = 0; } else if (prm > 100) { prm = 100; }
		if(config["volume"] == 0 && prm == 0) { prm = 80; }
		config["volume"] = prm;
		sendChange("volume",prm);
		playerSO.data.volume = prm;
		playerSO.flush();
	};


	/** Fullscreen switch function. **/
	private function setFullscreen() {
		if(Stage["displayState"] == "normal" && 
			config["usefullscreen"] == "true") { 
			Stage["displayState"] = "fullScreen";
		} else if (Stage["displayState"] == "fullScreen" && 
			config["usefullscreen"] == "true") {
			Stage["displayState"] = "normal";
		} else if (config["fsbuttonlink"] != undefined) {
			getURL(config["fsbuttonlink"],config["linktarget"]);
		}
	};


	/** Captions toggle **/
	private function setCaptions() {
		if(config["usecaptions"] == "true") {
			config["usecaptions"] = "false";
			config["clip"].captions._visible = false;
			config["clip"].controlbar.cc.icn._alpha = 40;
		} else {
			config["usecaptions"] = "true";
			config["clip"].captions._visible = true;
			config["clip"].controlbar.cc.icn._alpha = 100;
		}
		playerSO.data.usecaptions = config["usecaptions"];
		playerSO.flush();
	};


	/** Captions toggle **/
	private function setAudio() {
		if(config["useaudio"] == "true") {
			config["useaudio"] = "false";
			config["clip"].audio.setStop();
			config["clip"].controlbar.au.icn._alpha = 40;
		} else {
			config["useaudio"] = "true";
			config["clip"].audio.setStart();
			config["clip"].controlbar.au.icn._alpha = 100;
		}
		playerSO.data.useaudio = config["useaudio"];
		playerSO.flush();
	};


}