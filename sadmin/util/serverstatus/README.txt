[PHP]시스템 정보를 출력하는 PHP 스크립트

작성자 : 김칠봉[닉:산이] san2(at)linuxchannel.net


** 주의 사항 **

  1. 최소한 PHP exec() 함수를 사용 가능해야 합니다.
  2. 사용상 부주의로 인한 피해는 어떠한 경우라도 본 작성자에게 그 책임이 없음을 알립니다.

테스트 환경

  - RedHat, debian, slackware, JBLinux, ...
  - Apache 1.3.x/2.0.x
  - PHP 4.0.x/4.1.x/4.2.x (not support PHP/3.0.x)
  - x86(i386) architecture
  - IBM S/390 architecture


출력한 정보

  - 마더보드 호스트브리지(칩 종류)
  - CPU 정보
  - 메모리정보
  - VAG 카드
  - AGP 버스(옵션)
  - SCSI 컨트롤러(옵션)
  - 스왑메모리정보
  - 각 파티션 정보
  - 이더넷 정보
  - SCSI-HDD 정보(옵션)
  - EIDE-HDD 정보(옵션)
  - 커널버전 및 배포판 정보
  - 시스템/웹서버 구동 시간(Uptime)
  - 네트워크 devices 패킷 통계(/proc/net/dev)
  - 기타


소스가 있는 곳

  - http://ftp.linuxchannel.net/devel/sysinfo.tar


적용 예

  - http://www.linuxchannel.net/devel/sysinfo.php


홈페이지 적용 예

  - http://www.linuxchannel.net/?vhost=sysinfo


Changes

 - 2003.06.04(25차)
      fixed : for PHP/4.3.2 $size *= 1024 to $size = $size * 1024;
      (it's bug ???)

 - 2003.01.22(24차)
      renew realuri()
      add $_SERVER[_PWD], $_SERVER[_URI](for any path includes)
      moved, html.php, misc.php to class.utils.php

 - 2002.12.15(23차)
      block string parsing replace : preg_replace() -> str_replace()

 - 2002.11.14(22차)
      fixed runtime() function

 - 2002.11.07(21차)
      add boot time, btime(/proc/uptime -> btime), another(/proc/stat)
      optimizing preg_replace() argument 1

 - 2002.10.27(20차)
      Apache chroot environment check and patch
      ugly partitions display patch
      support 'short_open_tag off': PHP open tag change to '<?php ?>'

 - 2002.10.23(19차)
      support IBM S/390 architecture
      (자료및 테스트 공간제공 : "Jae-hwa Park" kingcrab(at)linux.sarang.net)
      HDD table check, /usr/src/linux/Documentations/devices.txt
      HDD major number 추가

 - 2002.10.15(18차)
      sysinfo.php -> class.sysinfo.php

 - 2002.08.27(17차)
      함수 전면 수정(speed up)
      템플렛 적용(func/config.php => templates/$TMPL[config]/*.tmpl)

 - 2001.07.01(16차)
      GET_PART() 함수 수정(파티션 종류 추가)
      GET_Ethernet() 함수 수정
      GET_NETSTAT() 함수 추가(네트워크 devices 통계)
      그 외 PHP 함수 파일로 분리

 - 2001.03.26(15차)
      GET_SCSI_HDD() 함수 수정(보완 aic7xxx)
      그외(Konqueror에서 이미지 수정 nowrap)

 - 2001.03.18(14차)
      Buffered/Cached 메모리 정보 추가(계산값) 및 이미지 통일

 - 2001.03.03(13차)
      커널 2.4.x에서 SCSI-HDD 수용용량 휴맨스케일로 보정
      SCSI Dual Channel 표시(옵션)
      기타

 - 2001.03.02(12차)
      부팅후 파티션 재 조정시 문제 해결 ...........not good!!! T.T
      스왑 파티션 GET_SWPART() 함수 추가
      컴팩서버에서 테스트 미완료........

 - 2001.02.25(11차)
      Buffered/Cached 메모리 정보 추가, 문제점 지적 : 문태준 taejun(at)tunelinux.pe.kr

 - 2001.02.25(10차)
     파티션 합계 문제 재수정......T.T
     기타 사소한 문제 수정

 - 2001.02.10(9차)
     커널 2.4.x에서 E-IDE HDD 정보 수정
     기타 사소한 버그 패치(커널 2.4.x)
     df 명령어 퍼미션 문제

 -  2001.02.07(8차)
     배포판 종류(Release) 정보 추가 GET_OS() 함수
     커널 2.4.x에서 BogoMIPS 값 출력문제 수정(커널 2.2.x 포함)
     커널 2.4.x에서 SCSI-HDD 정보 수정 (커널 2.2.x 포함)
     시스템 uptime 시간 추가
     아파치 uptime 시간 추가
     커널 2.4.x에서 파티션(devfsd)테스트 못했음
     기타

  - 2001.01.29 (7차)
     생각이 ??? T.T

  - 2000.10.10 (6차)
     오래된 E-IDE HDD일 경우 잘못된 정보로 인한 Get_EIDE_HDD() 함수 수정

  - 2000.10.07 (5차)
     SCSI 컨트롤러 정보 출력 문제 수정 Get_PCI(), /proc/scsi/*/0 에 정보가 없을 경우의 문제
     SCSI HDD 정보 출력 문제 수정 GET_SCSI_HDD() 전송 속도 출력 문제, /proc/scsi/*/0 에 정보가 없을 경우의 문제

  - 2000.10.02 (4차)
     파티션 합계 문제 수정
     오류 보고  : 모성진 msjinny(at)myplan.co.kr, 손대수 dsson(at)korix.com
     Get_PCI() 함수 수정 및 Addon 카드가 있을 경우 추가
     레드햇 7.0의 변경된 /etc/modules.conf  파일 이름으로 인한 Get_Ethernet() 함수 수정
     Get_EIDE() 함수 수정

  - 2000.09.18 (3차)
     HTML 출력 및 마무리

  - 2000.08.04 (2차)
     함수 정리

  - 2000.07.?? (1차)
     Layout 및 초기 결과


-- END --
