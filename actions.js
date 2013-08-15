// подтверждение на удаление
function del( par, obj, filename, name_del )
{
	if (confirm("Вы действительно хотите удалить '"+name_del+"'?"))
	{
		window.open("/ns/sys/"+filename+".php?par="+par+"&obj="+obj+"&act=del_now", "", "width=970, height=600, left=50, top=50, toolbar=no, status=yes, resizable=yes, scrollbars=yes");
	}
}

function delta()
{
	document.cookie="click=yes; path=/;";
	form1.target="_self";
	return;
}

function myShowImgPicker(mname)
{
	window.open('get_file.php?mname='+mname, '_blank', 
  'height=420,width=420,toolbars=no,resizable=no,status=no');
}






// Просмотр изображений
function look_img ( uri )
{
	window.open( uri, "editor", "width=900, height=600, left=50, top=50, toolbar=no, status=yes, resizable=yes, scrollbars=yes");
}

// Закрытие окна редактора и обновление навигационного фрейма в главном окне
function rel_naf ()
{
	window.close();
	opener.location.reload();
}

// Сохраниение положения каретки в поле формы
function storeCaret ( textEl )
{
	if (textEl.createTextRange) 
	textEl.caretPos = document.selection.createRange().duplicate();
}

// Вставка заданного текста в поле формы в область местоположения каретки
function ins (textEl, text)
{
	if ( navigator.userAgent.indexOf("MSIE") != -1  )
	{
		if ( textEl.createTextRange && textEl.caretPos )
		{
			var caretPos = textEl.caretPos;
			caretPos.text =	caretPos.text.charAt ( caretPos.text.length - 1 ) == ' ' ?	text + ' ' : text;
		}
		else
		textEl.value  = text;
	}
	else
	textEl.value = textEl.value+text;
}

function init()
{
	if (navigator.appName == "Netscape") 
		{
			layerRef="document.layers"; 
			styleSwitch=""; 
			visibleVar="show";
		}
	else
		{
			layerRef="document.all"; 
			styleSwitch=".style"; 
			visibleVar="block";
			visibleVar2="inline";
      	}
} 

// Открытие слоя
function s(layerName)
{
	eval(layerRef+'["'+layerName+'"]'+styleSwitch+'.display="block"');
}

// Сокрытие слоя
function h(layerName)
{
     eval(layerRef+'["'+layerName+'"]'+styleSwitch+'.display="none"');
}

// переключатель сокрытия открытия двух слоев
function sh( layer1 )
{
     if (eval(layerRef+'["'+layer1+'"]'+styleSwitch+'.display == visibleVar')){ 
     h (layer1)
     layer1=0; 
     }else{ 
     s (layer1)
     layer1=1;
     }
}

// переключатель сокрытия открытия двух слоев
function sh2( layer1 )
{
     if (eval(layerRef+'["'+layer1+'"]'+styleSwitch+'.display == visibleVar2')){ 
     h (layer1)
     layer1=0; 
     }else{ 
     eval(layerRef+'["'+layer1+'"]'+styleSwitch+'.display="inline"');
     layer1=1;
     }
}

var fileInput;

function BrowseServer(obj)
{
	fileInput = obj;
	// You can use the "CKFinder" class to render CKFinder in a page:
	var finder = new CKFinder() ;
	finder.BasePath = '../_gpl/ckfinder/' ;	// The path for the installation of CKFinder (default = "/ckfinder/").
	finder.SelectFunction = SetFileField ;
	finder.Popup() ;
	return false;
	// It can also be done in a single line, calling the "static"
	// Popup( basePath, width, height, selectFunction ) function:
	// CKFinder.Popup( '../../', null, null, SetFileField ) ;
	//
	// The "Popup" function can also accept an object as the only argument.
	// CKFinder.Popup( { BasePath : '../../', SelectFunction : SetFileField } ) ;
}

// This is a sample function which is called when a file is selected in CKFinder.
function SetFileField( fileUrl )
{
	document.getElementById( fileInput ).value = fileUrl ;
}
