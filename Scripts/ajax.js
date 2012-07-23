var xhrObject;

function CreateXHR() {
    if(window.ActiveXObject) {
        httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
    } else {
        httpRequest = new XMLHttpRequest();
    }
    alert("xhr created");
    return httpRequest;
}
    
function SendAjaxRequest(url, callFunction, method, argument) {
        
    if(callFunction == null) {
        return false;
    }
    if(method == null) {
        method = "GET";
    }

    alert("SendAjaxRequest called\ncallFunction: "+callFunction+"\nmethod: "+method+"\nurl: "+url);
        
    xhrObject.Open(method, url, true);
    xhrObject.onreadystatechange = callFunction();

    if(argument != null) {
        xhrObject.Send(argument);
    } else {
        xhrObject.Send(null);
    }
    return true;
}

function GetResponse(format) {
    if(format === 'TEXT') {
        return xhrObject.responseText;
    } else if(format == 'XML') {
        return xhrObject.responseXML;
    }
    return false;
}