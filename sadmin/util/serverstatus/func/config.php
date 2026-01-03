<?php
##  시스템 정보 출력 #############################################
##
##  작성자	: 김칠봉[닉:산이] <san2(at)linuxchannel.net>
##  스크립트 명 : PHP를 이용한 시스템 정보를 출력하는 스크립트
##
#############################################################
##
## 주)
## 사용상 부주의로 인한 피해는
## 본 작성자에게 어떠한 보증이나 책임이 없습니다.
##
###############################################################

## 템플렛 설정
## default
## simple
## gray
## line
## lsn-bluesky
## phpschool
## linuxchannel
## kde1
##
$tmpl['default']	= '기본값';
$tmpl['simple']		= 'SIMPLE';
$tmpl['line']		= 'SIMPLE LINE';
$tmpl['gray']		= '회색';
$tmpl['lsn-bluesky']	= 'LSN BLUESKY';
$tmpl['phpschool']	= 'PHPSCHOOL.COM';
$tmpl['linuxchannel']	= 'LINUXCHANNEL.NET';
$tmpl['kde1']		= 'KDE 1';

//$tmpl_config = 'default'; // static configuration (if not given 'select')
$tmpl_config = 'select'; // and 'select' -> user select

## 빌드 버전
##
define('_SYSINFO_VERSION_','20030604');
?>
