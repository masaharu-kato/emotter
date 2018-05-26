
function switch_class(elm, class_name) {

	if(!elm.classList.contains(class_name)) {
		elm.classList.add   (class_name);
	}else{
		elm.classList.remove(class_name);
	}

}

function switch_elm_class(elm_id, class_name) {
	return switch_class(document.getElementById(elm_id), class_name);
}
