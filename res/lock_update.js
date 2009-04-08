
var lockBoxes = "";

var isIE = 0;


if (navigator.userAgent.search(/MSIE/)!=-1)	{
	isIE = 1;
}


function updateBrowseTree(table, uid, isLocked, box, msg)	{
	if (table!='pages')	{
		return;
	}
	var navWin = getT3document("nav_frame");
	if (!navWin) {
		return;
	}
	var liTag = navWin.getElementById('pages'+uid+'_0');
	if (!liTag) return;
	if (!liTag.childNodes) return;
	var cn = "";
	var cj = "";
	var hasLockIcon = false;
	for (var i = 0; i < liTag.childNodes.length; i++)	{
		cn = liTag.childNodes[i];
		if (!cn) continue;
		if (cn.nodeType != 1) continue;
		if (cn.tagName=='A')	{
			var pos = cn.innerHTML.search(/recordlock_warning3/);
			if (pos != -1)	{
				hasLockIcon = true;
				if (!isLocked)	{
					cn.parentNode.removeChild(cn);
					return;
				}
			}
		}
		if (cn.id=='dragTitleID_'+uid)	{
			if (isLocked)	{
				if (hasLockIcon) return;
				msg = msg.replace(/\.\.\/\.\.\/\.\.\/gfx\/recordlock_warning3\.gif/, 'gfx/recordlock_warning3.gif');
				if (!msg) return;
				if (!msg.length) return;
				if (isIE)	{
					cn.insertAdjacentHTML("BeforeBegin", msg);
				} else	{
					var divTag = document.createElement("DIV");
					divTag.innerHTML = msg;
					var aTag = divTag.childNodes[0];
					cn.parentNode.insertBefore(aTag, cn);
				}
				return;
			}
		}
	}
}


function handleLockCheck(data)	{
	lockBoxes = "";
	currentRequest = 0;
	if (data.childNodes)	{
		for (var i = 0; i < data.childNodes.length; i++)	{
			if (!isNaN(Number(i)))	{
				if (data["childNodes"][i]["nodeType"]==1)	{
					if (data["childNodes"][i]["tagName"]=="again")	{
						checkLock();
						return;
					} else if (data["childNodes"][i]["tagName"]=="records")	{
						var recs = data["childNodes"][i]["childNodes"];
						for (var j = 0; j < recs.length; j++)	{
							if (recs[j]["tagName"]=="record")	{
								handleLockedRec(recs[j]["childNodes"]);
							}
						}
					} else if (data["childNodes"][i]["tagName"]=="messages")	{
						var recs = data["childNodes"][i]["childNodes"];
						if (recs.length) {
							for (var j in recs) {
								if (recs[j]["tagName"]=="message")  {
									handleMessage(recs[j]["childNodes"]);
								}
							}
						}
					} else	{
						alert("Invalid AJAX reply !");
					}
				}
			}
		}
		var lockBoxesSpan = document.getElementById("lockBoxes");
		if (lockBoxesSpan && ((lockBoxesSpan.innerHTML && !lockBoxes) || (lockBoxes && !lockBoxesSpan.innerHTML)))	{
			lockBoxesSpan.innerHTML = lockBoxes;
		}
		checkLock();
	}
}

function handleMessage(data)	{

	if (!data) return;

	var from_uid = parseInt(data[0].textContent);
	var from_username = data[1].textContent;
	var message = data[2].textContent;
	var tstamp = parseInt(data[3].textContent);
	var urgent = parseInt(data[4].textContent);

	if (urgent)	{
		showMessage(from_username, from_uid, message, tstamp);
	} else	{
		addMessage(from_username, from_uid, message, tstamp);
	}
}

var messages = Array();

function addMessage(user, uid, message, tstamp)	{
	messages.push(Array(user, uid, message, tstamp));
	var messageLink = document.getElementById('message-link');
	messageLink.style.display = "block";
}

function showStoredMessages()	{
	var message = messages.shift();
	showMessage(message[0], message[1], message[2], message[3]);
	if (messages.length==0)	{
		var messageLink = document.getElementById('message-link');
		messageLink.style.display = "none";
	}
}

function showMessage(user, uid, message, tstamp)	{
	var shade = document.createElement("DIV");
	shade.setAttribute("class", "body-shade");
	shade.setAttribute("id", "body-shade");

	var box = document.createElement("DIV");
	box.setAttribute("class", "message-box");
	box.setAttribute("id", "message-box");
	var dObj = new Date(tstamp*1000);
	var month = dObj.getMonth()+1;
	if (month<10) {
		month = '0'+month
	}
	var day = dObj.getDate();
	if (day<10) {
		day = '0'+day;
	}
	var hours = dObj.getHours();
	if (hours<10) {
		hours = '0'+hours;
	}
	var minutes = dObj.getMinutes();
	if (minutes<10) {
		minutes = '0'+minutes;
	}
	var time = dObj.getFullYear()+"-"+month+"-"+day+" "+hours+":"+minutes;
	box.innerHTML = '<h1>Message received !</h1><h2>From User: '+user+' ('+uid+')</h2><h3>Received at: '+time+'</h3><p>'+message+'</p><a href="#" onclick="return closeMessage();">Close</a>';

	document.body.appendChild(shade);
	document.body.appendChild(box);

	var left = Math.round((window.innerWidth-400)/2);
	var top = Math.round((window.innerHeight-box.offsetHeight)/2);
	if (left<0)	{
		left = 0;
	}
	if (top<0)	{
		top = 0;
	}
	box.setAttribute("style", "left: "+left+"px; top: "+top+"px; visibility: visible;");
}

function closeMessage()	{
	var shade = document.getElementById('body-shade');
	var box = document.getElementById('message-box');
	box.parentNode.removeChild(box);
	shade.parentNode.removeChild(shade);
}


var Gcnt = 0;

function getT3document(which) {
/*
// Check this in IE6 & IE7
	if (top.content[which]) {
		return top.content[which].document;
	} else {
	}
*/
	var contentObj = top.document.getElementById('content');
	var whichObj = contentObj.contentDocument.getElementsByName(which);
	if (whichObj.length>0) {
		var frameObj = whichObj[0];
		if (frameObj.document) {
			return frameObj.document;
		} else if (frameObj.contentDocument) {
			return frameObj.contentDocument;
		}
	}
	return false;
}

function handleLockedRec(data, pageUid, pageLocked) {
	if (!(data||pageUid)) {
		return;
	}
	if (pageUid && !data) {
		var isLocked = pageLocked;
		var table = "pages";
		var uid = pageUid;
		var box = "";
		var msg = "";
	} else {
		var isLocked = parseInt(getXMLvalue(data, 0));
		var table = getXMLvalue(data, 1);
		var uid = parseInt(getXMLvalue(data, 2));
		var user = getXMLvalue(data, 3);
		var age = getXMLvalue(data, 4);
		var type = getXMLvalue(data, 5);
		var pid = getXMLvalue(data, 6);
		var contentLocked = getXMLvalue(data, 7);
		var explicit = parseInt(getXMLvalue(data, 8));
//		alert("table: "+table+"     uid: "+uid);
		if (type=="content")	{
			var msg = msg_lockedContent;
		} else {
			var msg = msg_lockedRecord;
		}
		if (isLocked) {
			msg = msg.replace(/###USER###/g, user);
			msg = msg.replace(/###AGE###/g, age);
		} else {
			msg = "";
		}
	}
	if ((table=="pages")&&(uid=="-1"))	{
		for (var j in pageData)	{
			var jp = j.split("-");
			if (jp[0]!="pages") continue;
			var pId = parseInt(jp[1]);
			var pLock = pageData[j];
			if (pLock)	{
				handleLockedRec(0, pId, 0);
				lockData["pages-"+pId] = 0;
			}
		}
		pageData = Array();
		return;
	}
	lockData[table+"-"+uid] = isLocked?(explicit?2:1):0;
	if (table=='pages') {
		if ((!contentLocked) || top.pageContentEditWarning) {
			updateBrowseTree(table, uid, isLocked, box, msg);
		}
	}


	/* Handle Translated pages --- begin */
	/*
	if ((top.lockingAcrossLanguages) &&(table=='pages_language_overlay'))	{
		updateBrowseTree("pages", pid, isLocked, box, msg);
	}
	*/
	/* Handle Translated pages --- end */


	lockBoxes += box;


	var contentWin = getT3document("list_frame");
	if (contentWin) {
		var obj = contentWin.getElementById("lockIcon-"+table+"-"+uid);
		if (obj) {
			if (msg) {
				obj.storeContent = obj.innerHTML;
				obj.innerHTML = msg;
			} else {
				if (obj.storeContent) {
					obj.innerHTML = obj.storeContent;
				} else {
				// TODO: Check if this is ok in all cases
					obj.innerHTML = '<img src="clear.gif" width="17" height="1" />';
				}
			}
		}
		if (((disableEditIconsOnLock && (!contentLocked)) || (disableEditIconsOnLock_contentLocked && contentLocked)) && !explicit) {
			var id = "disableOnLock-"+table+"-"+uid+"-";
			var i = 0;
			while (1)	{
				var useid = id+(i++);
				var obj = contentWin.getElementById(useid);
				if (obj)	{
					if (isLocked)	{
						if (changeVisibility)	{
							obj.style.visibility = "hidden";
						} else	{
							obj.style.display = "none";
						}
					} else	{
						if (changeVisibility)	{
							obj.style.visibility = "visible";
						} else	{
							obj.style.display = "inline";
						}
					}
				} else	{
					break;
				}
			}
		}
	}
}

function getXMLvalue(data, num)	{
	var ret = "";
	if (data["noXML"]) {
		ret = data[num];
	} else if (data[num].childNodes.length)	{
		ret = data[num].childNodes[0].nodeValue;
	}
	return ret;
}

