﻿/**
* Rotator extension of the controller.
*
* @author	Jeroen Wijering
* @version	1.4
**/


import com.jeroenwijering.players.AbstractController;


class com.jeroenwijering.players.RotatorController extends AbstractController{


	/** Which one of the models to send the changes to **/
	private var currentModel:Number;


	/** Constructor, inherited from super **/
	function RotatorController(car:Object,ply:Object) { 
		super(car,ply); 
	};


	/** Complete the build of the MCV cycle and start flow of events. **/
	public function startMCV(mar:Array) {
		registeredModels = mar;
		sendChange("item",currentItem);
		if(config["autostart"] == "false") {
			sendChange("start",0);
			sendChange("pause",0);
			isPlaying = false;
		} else { 
			sendChange("start",0);
			isPlaying = true;
		}
	};


	/** PlayPause switch **/
	private  function setPlaypause() {
		if(isPlaying == true) {
			isPlaying = false;
			sendChange("pause");
		} else { 
			isPlaying = true;
			sendChange("start");
		}
	};


	/** Play previous item. **/
	private  function setPrev() {
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
			sendChange("stop");
			itm > feeder.feed.length-1 ? itm = feeder.feed.length-1: null;
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
		if(config["repeat"]=="false" || (config["repeat"] == "list"
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


	/** Switch active model and send changes. **/
	private function sendChange(typ:String,prm:Number):Void {
		if(typ == "item") { 
			currentModel == 0 ? currentModel = 1: currentModel = 0;
		}
		registeredModels[currentModel].getChange(typ,prm);
	};


}