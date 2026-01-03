/**
 * @license Copyright (c) 2003-2020, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';	
	
	config.skin = 'moono-lisa';
	config.toolbar = 'User';
	config.enterMode = '2'; //엔터키 태그 1:<p>, 2:<br>, 3:<div>
	config.shiftEnterMode = '2'; //쉬프트+엔터키 태그 1:<p>, 2:<br>, 3:<div>
	config.toolbarCanCollapse = true;
	//config.removeDialogTabs = 'link:target;link:advanced;image:Link;image:advanced';
	config.filebrowserImageUploadUrl = '/ckeditor/uploader/editor_upload.php?path=board2';
	config.filebrowserUploadUrl = '/ckeditor/uploader/editor_upload.php?path=board2';
	config.filebrowserWindowWidth = '640';
	config.filebrowserWindowHeight = '480';
	config.comma = '';

	config.language = 'ko';		  // 언어설정
	//config.uiColor = "#F0F0F0";	// UI색상변경
	config.height = '560px';		  // CKEditor 높이  
	config.width = '100%';		   // CKEditor 넓이	

	config.enterMode = CKEDITOR.ENTER_BR;			// Enter 입력시 <br/> 태그 변경
	config.shiftEnterMode = CKEDITOR.ENTER_P;		// Enter 입력시 <p> 태그 변경
	config.startupFocus = true;								  // 시작시 포커스 설정
	//config.font_defaultLabel = 'Gulim';						// 기본 글씨 폰트
	//config.font_names = 'Gulim/Gulim;Dotum/Dotum;Batang/Batang;Gungsuh/Gungsuh;' + config.font_names;	// 사용가능한 기타 폰트 설정
	config.fontSize_defaultLabel = '10px';				   // 기본 글씨 폰트 사이즈
	
};

CKEDITOR.on( 'instanceCreated', function( e ){
	e.editor.addCss('img {max-width:990px; width: expression(this.width > 990 ? 990: true); height: auto;}');
});
