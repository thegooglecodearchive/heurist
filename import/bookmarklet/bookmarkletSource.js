javascript:(function(){h='base URL goes here with trailing /';d=document;c=d.contentType;if(c=='text/html'||!c){if(d.getElementById('__heurist_bookmarklet_div'))return Heurist.init();s=d.createElement('script');s.type='text/javascript';s.src=(h+'import/bookmarklet/bookmarkletPopup.php?'+new Date().getTime()).slice(0,-8);d.getElementsByTagName('head')[0].appendChild(s);}else{e=encodeURIComponent;w=open(h+'records/add/addRecordPopup.php?t='+e(d.title)+'&u='+e(location.href));window.setTimeout('w.focus()',200);}})();