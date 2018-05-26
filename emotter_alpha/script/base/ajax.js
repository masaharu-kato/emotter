
function loadByAjax(ajax_url, method, params, func) {

    console.log('loadByAjax', ajax_url, method, params, func);

    $.ajax({
        type: method,
        url: ajax_url,
        data: params,
        
    //	送信前：AJAXによる読み込みであることの情報を付加する
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-CSRF-Token', $('meta[name="csrf-token"]').attr('content'));
        },

    //	受信後、成功したとき：読み込んだコンテンツを反映させる
        success: func,

    //	受信後、失敗したとき：エラーを表示させる
        error: function(XMLHttpRequest, textStatus, errorThrown){
        	alert("エラー: AJAXによる読み込みに失敗しました。\n"
          		+ 'Error : ' + errorThrown + "\n"
        		+ 'XMLHttpRequest : ' + XMLHttpRequest.status + "\n"
        		+ 'textStatus : ' + textStatus + "\n"
        		+ 'errorThrown : ' + errorThrown + "\n"
        	);
        }

    });

}
