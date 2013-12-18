window.SmartWiki = {
	
	arr : null,
	
	loaded : false,
	
	state : '',
	
	textEmpty : 'Hide empty attributes',
	textNotEmpty : 'Show empty attributes',
		
	start : function() {
		if ( this.loaded == true ) {
			return;
		}
		this.loaded = true;
		
		this.collectData();
		this.drawTable();
		this.addHideShowLink();
		this.hideEmpty();
		
	},
	
	addHideShowLink : function(){
		var link = "<span class='softlink' onClick='SmartWiki.switchEmpty(this);'>"+this.textNotEmpty+"</span>";
		$($(".smartwikiTableView")[0]).before(link);
	},
	
	switchEmpty : function(el) {
		if ( this.state == 'hidden' ) {
			this.showEmpty();
			el.innerHTML = this.textEmpty;
		} else {
			this.hideEmpty();
			el.innerHTML = this.textNotEmpty;
		}
		
	},
	
	collectData : function() {
		var myArray = {};
		var ac = $(".associationClass");
		for(var i = 0; i < ac.length; i++ ) {	// For each association class block
			var aName = $($($(".associationClass")[i]).parentsUntil("tr").parent().children()[0]).html();
			if ( typeof(myArray[aName]) == 'undefined' ) {
				myArray[aName] = { 'headers' : [], 'info' : [], 'data' : [] };
			}
			
			// Get data
			var b = {};
			var aTRs = $($(".associationClass")[i]).find("tr");
			for(var j = 0; j<aTRs.length; j++) { // For each attribute
				var label = $($($(".associationClass")[i]).find("tr")[j]).find(".label").html();
				var info = $($($(".associationClass")[i]).find("tr")[j]).find(".info").html();
				var input = $($($(".associationClass")[i]).find("tr")[j]).find(".input").html();
				
				if ( $.inArray(label, myArray[aName].headers) == -1 ) {
					myArray[aName].headers.push(label);
					myArray[aName].info.push(info);
				}
				b[label] = input;
			}
			
			myArray[aName].data.push(b);
			
		}
		this.arr = myArray;
		
	},

	drawTable : function() {
		var myArray = this.arr;
		
		var ac = $(".associationClass");
		for(var i = 0; i<ac.length; i++ ) {	// For each association class block
			var aName = $($(ac[i]).parentsUntil("tr").parent().children()[0]).html();
			if ( typeof(myArray[aName]) == 'undefined' || myArray[aName] == null ) {
				$(ac[i]).parentsUntil("table").parent().remove();
			} else {
				 // build table
				var html = '';
				html = '<table><thead><tr>';
				for(var j = 0; j < myArray[aName].headers.length; j++ ) {
					html = html + '<th>' + myArray[aName].headers[j] + '&nbsp;' + myArray[aName].info[j] + '</th>';
				}
				html = html + '</tr></thead>';
				html = html + '<tbody>';
				for(var j = 0; j < myArray[aName].data.length; j++ ) {
					html = html + '<tr>';
					for(var k = 0; k < myArray[aName].headers.length; k++ ) {
						html = html + '<td>' + myArray[aName].data[j][myArray[aName].headers[k]] + '</td>';
					}
					html = html + '</tr>';
				}
				html = html + '</tbody>';
				html = html + '</table>';
				ac[i].innerHTML = html;
				myArray[aName] = null;
			}
		}
	},
	
	hideEmpty : function() {
		var n = $(".smartwikiTableView > tbody > tr");
		for(var i = 0; i < n.length; i++ ) {
			var input = $(n[i]).find(".input");
			if ( input.html() == "" ) {
				// hide this
				$(n[i]).css('display', 'none');
			} else {
				$(n[i]).css('display', '');
			}
		}
		this.state = 'hidden';
	},
	
	showEmpty : function() {
		var n = $(".smartwikiTableView > tbody > tr");
		for(var i = 0; i < n.length; i++ ) {
			var input = $(n[i]).find(".input");
			$(n[i]).css('display', '');
		}
		this.state = '';
	},
	
	addPrefix : function(elementName, prefixToAdd) {
		var elJq = $("[name='"+elementName+"']");
		if ( elJq.length != 1 ) {
			alert('Fatal error: Element '+elementName+' was not found. Cannot create page.');
			return false;
		}
		var el = elJq[0];
		if ( el.value.substr(0,prefixToAdd.length) == prefixToAdd ) {
			return true;
		}
		
		el.value = prefixToAdd + el.value;
		
		return true;
	}
	
	
	
	
};

$("document").ready(function() {
	SmartWiki.start();
});
