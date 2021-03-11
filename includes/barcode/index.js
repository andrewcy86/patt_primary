var Quagga = window.Quagga;
var backCamID = null;
var last_camera = null;
// initialize array
var barcode_arr = [];
var loc_arr = [];
loc_count = 0;
loc_barcode = '';
box_pallet_count = 0;
//document.getElementById("box_pallet_count").innerHTML='<span id="box_pallet_count_num" style="font-size: 35pt"></span>';
//document.getElementById("box_pallet_count").innerHTML='<span id="box_pallet_count_submit"></span>';
document.getElementById("box_pallet_count").innerHTML='<span id="box_pallet_count_num" style="font-size: 35pt"></span><span style="color:red"></span><span id="box_pallet_count_submit"></span>';

Array.prototype.remove = function() {
    var what, a = arguments, L = a.length, ax;
    while (L && this.length) {
        what = a[--L];
        while ((ax = this.indexOf(what)) !== -1) {
            this.splice(ax, 1);
        }
    }
    return this;
};

// define all regular expressions

// location
const reg_physicalLocation = /^\d{1,3}A_\d{1,3}B_\d{1,3}S_\d{1,3}P_(E|W|ECUI|WCUI)$/i;
    
//const reg_cartid = /(\bCID-\d\d-E\b|\bCID-\d\d-W\b)|(\bCID-\d\d-EAST\sCUI\b|\bCID-\d\d-WEST\sCUI\b)|(\bCID-\d\d-EAST\b|\bCID-\d\d-WEST\b)|(\bCID-\d\d-EASTCUI\b|\bCID-\d\d-WESTCUI\b)/gim;

const reg_cartid = /^\b(CID-\d\d-E|CID-\d\d-W)\b$/i;

const reg_stagingarea = /^\b(SA-\d\d-E|SA-\d\d-W)\b$/i;

const reg_scanning = /^\b(SCN-\d\d-E|SCN-\d\d-W)\b$/i;

// boxes and pallets
const reg_boxid = /^\d{7}-\d{1,3}$/i;
const reg_palletid = /^(P-(E|W)-[0-9]{1,5})*$/i;
    
function removeParent(evt){

  if (reg_cartid.test(evt) || reg_stagingarea.test(evt) || reg_stagingarea.test(evt) || reg_scanning.test(evt) || reg_physicalLocation.test(evt)){
    loc_count = loc_count-1;
  barcode_arr.remove(evt);
  document.getElementById(evt).outerHTML = "";
  document.getElementById("location").value = loc_barcode;
  }
  
  if (reg_boxid.test(evt) || reg_palletid.test(evt)) {
    box_pallet_count = box_pallet_count-1; 
    loc_arr.remove(evt);
    barcode_arr.remove(evt);
  document.getElementById(evt).outerHTML = "";
  }

 if (box_pallet_count === 0 || loc_count === 0) {
    document.getElementById("box_pallet_count_submit").innerHTML = '';
 }
document.getElementById("box_pallet_count_num").innerHTML = box_pallet_count;
  //alert(x);
  //alert(loc_count);
  
//Array Update
var hidden_arry = document.getElementById("box_pallet_array");
hidden_arry.value = loc_arr.join(",");

}

// Implement rear camera by default fix
navigator.mediaDevices.enumerateDevices()
.then(function(devices) {
  devices.forEach(function(device) {  
 if( device.kind == "videoinput" && device.label.match(/back/) !== null ){
      backCamID = device.deviceId;
    }
if( device.kind === "videoinput"){
last_camera = device.deviceId;
} 
});
if( backCamID === null){
backCamID = last_camera;
}
})
.catch(function(err) { 

});

var App = {
    _lastResult: null,
    init: function() {
        this.attachListeners();
    },
    activateScanner: function() {
        const htmlVideoElements = document.getElementsByTagName('video')
        var scanner = this.configureScanner('.overlay__content'),
            onDetected = function (result) {
                this.addToResults(result);
            }.bind(this),
            stop = function() {

            console.log(barcode_arr);
        //Fix freezing on Andorid 11
        for (let i = 0; i < htmlVideoElements.length; ++i) {
            const targetElement = htmlVideoElements[i]
            if (targetElement && targetElement.pause) {
                targetElement.pause();
            }
        }
        
        setTimeout(() => {
            scanner.stop();
              // should also clear all event-listeners?
        }, 10);
            scanner.removeEventListener('detected', onDetected);
            this.hideOverlay();
            this.attachListeners();
            }.bind(this);
                
        this.showOverlay(stop);
        
        console.log("activateScanner");
        
        for (let i = 0; i < htmlVideoElements.length; ++i) {
            const targetElement = htmlVideoElements[i]
            if (targetElement && targetElement.play) {
                targetElement.play();
            }
        }
               setTimeout(() => {
        scanner.addEventListener('detected', onDetected).start();
              // should also clear all event-listeners?
        }, 10);

    },
    addToResults: function(result) {
        if (this._lastResult === result.codeResult.code) {
            return;
        }

        this._lastResult = result.codeResult.code;
        var results = document.querySelector('ul.results'),
            li = document.createElement('li'),
            format = document.createElement('span'),
            code = document.createElement('span');
        
    if (reg_cartid.test(result.codeResult.code) || reg_stagingarea.test(result.codeResult.code) || reg_stagingarea.test(result.codeResult.code) || reg_scanning.test(result.codeResult.code) || reg_physicalLocation.test(result.codeResult.code)){
        
        li.className = "result_location";
        
    } else {
        li.className = "result";
    }
        format.className = "format";
        code.className = "code";

        li.appendChild(format);
        li.appendChild(code);
        li.setAttribute('id',result.codeResult.code)

        var closeTag = document.createElement('div');
        
        closeTag.setAttribute('onclick', "removeParent('"+result.codeResult.code+"');");
        closeTag.setAttribute('class', "overlay__close_tag");
        closeTag.innerText = "X";

    // Check if a value exists in the array
    if(barcode_arr.indexOf(result.codeResult.code) !== -1){
        $('.ui-dialog-title').text('Details');
        $('.ui-dialog-content').html(result.codeResult.code+" has already been scanned.");

        $('#dialog_warn').dialog('open');
        //alert(result.codeResult.code+" has already been scanned.")
    } else{
    
    //alert (result.codeResult.code)
    if (result.codeResult.code !== '') {

    //check to see if barcode is a location, box or pallet
    if (reg_cartid.test(result.codeResult.code) || reg_stagingarea.test(result.codeResult.code) || reg_stagingarea.test(result.codeResult.code) || reg_scanning.test(result.codeResult.code) || reg_physicalLocation.test(result.codeResult.code)){

        // append new value to the array only once
        if (loc_count === 0) {
        increment_loc = ++loc_count; 
        barcode_arr.push(result.codeResult.code);
        loc_barcode = result.codeResult.code;
        li.appendChild(closeTag);
        format.appendChild(document.createTextNode('Location'));
        code.appendChild(document.createTextNode(result.codeResult.code));
        results.insertBefore(li, results.firstElementChild.nextSibling);
        //alert(loc_count);
        } else {
        $('.ui-dialog-title').text('Details');
        $('.ui-dialog-content').html('Only one location can be assigned at a time.');

        $('#dialog_warn').dialog('open');
        //alert('Only one location can be assigned at a time.');
        }
        
    } else {                 
    
    if(reg_boxid.test(result.codeResult.code)){
    //alert('Box');
            // append new value to the array
     if (box_pallet_count >= 0) {
        increment_box_pallet = ++box_pallet_count; 
        barcode_arr.push(result.codeResult.code);
        loc_arr.push(result.codeResult.code);
        li.appendChild(closeTag);
        format.appendChild(document.createTextNode('Box'));
        code.appendChild(document.createTextNode(result.codeResult.code));
        results.insertBefore(li, results.firstElementChild.nextSibling);
     }
    }
    
    if(reg_palletid.test(result.codeResult.code)){
    //alert('Pallet');
            // append new value to the array
     if (box_pallet_count >= 0) {
        increment_box_pallet = ++box_pallet_count; 
        barcode_arr.push(result.codeResult.code);
        loc_arr.push(result.codeResult.code);
        li.appendChild(closeTag);
        format.appendChild(document.createTextNode('Pallet'));
        code.appendChild(document.createTextNode(result.codeResult.code));
        results.insertBefore(li, results.firstElementChild.nextSibling);
     }
    }
    
    }

    }
    
    }
    
    },
    attachListeners: function() {
        var button = document.querySelector('button.scan'),
            self = this;

        button.addEventListener("click", function clickListener (e) {
            e.preventDefault();
            button.removeEventListener("click", clickListener);
            self.activateScanner();
        });
    },
    showOverlay: function(cancelCb) {
        document.querySelector('.container .controls')
            .classList.add('hide');
        document.querySelector('.overlay--inline')
            .classList.add('show');
        var closeButton = document.querySelector('.overlay__close');
        closeButton.addEventListener('click', function closeHandler() {
                    if(loc_count != 1) {
        $('.ui-dialog-title').text('Details');
        $('.ui-dialog-content').html('Please scan a location.');

        $('#dialog_warn').dialog('open');
            //alert('Please scan a location.'); 
                    } else if (box_pallet_count === 0) { 
        $('.ui-dialog-title').text('Details');
        $('.ui-dialog-content').html('Please scan a box/pallet.');

        $('#dialog_warn').dialog('open');
            //alert('Please scan a box/pallet.'); 
                    } else {
            //Site location value
            document.getElementById("location").value = loc_barcode;
            var hidden_arry = document.getElementById("box_pallet_array");
            hidden_arry.value = loc_arr.join(",");

            closeButton.removeEventListener("click", closeHandler);
            document.getElementById("box_pallet_count").innerHTML='<p style="font-size: 18pt; text-align: center;"><strong><span id="box_pallet_count_num" style="font-size: 35pt">'+box_pallet_count+'</span><br />Boxes/Pallets Scanned<br /> <span style="color:red">Location: '+loc_barcode+'</span></strong></p><span id="box_pallet_count_submit"><input class="icon-barcode button scan" type="submit" onclick="wppatt_barcode_assignment_update();" /></span>';

            cancelCb();
            }
        });
    },
    hideOverlay: function() {
        document.querySelector('.container .controls')
            .classList.remove('hide');
        document.querySelector('.overlay--inline')
            .classList.remove('show');
    },
    querySelectedReaders: function() {
        return Array.prototype.slice.call(document.querySelectorAll('.readers input[type=hidden]'))
            
            .map(function(element) {
                return element.getAttribute("value");
            });
    },
    configureScanner: function(selector) {
        var scanner = Quagga
            .decoder({readers: this.querySelectedReaders()})
            .locator({halfSample: false, patchSize: 'small'})
            .fromSource({
                target: selector,
                constraints: {
                    width: 600,
                    height: 600,
                    //facingMode: "environment"
                    deviceId: backCamID
                }
            });
        return scanner;
    }
};


App.init();