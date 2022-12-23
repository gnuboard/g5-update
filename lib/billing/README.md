# **그누보드 자동결제(빌링)**

## **요구사항**

<br>

## **설치**

### **1. 소스코드**
1. 압축파일 다운로드 후 압축 해제
2. 설치할 서버에 업로드합니다.
3. 🛠️`bbs/board.php` 파일에 `run_event('header_board');` 를 추가합니다. (18 Line)
   ```php
        if (!$bo_table) {
            $msg = "bo_table 값이 넘어오지 않았습니다.\\n\\nboard.php?bo_table=code 와 같은 방식으로 넘겨 주세요.";
            alert($msg);
        }
        // 게시판 진입 시 권한을 체크하기 위한 Hook 추가 
        run_event('header_board');

        $g5['board_title'] = ((G5_IS_MOBILE && $board['bo_mobile_subject']) ? $board['bo_mobile_subject'] : $board['bo_subject']);
   ```

### **2. 데이터베이스**
1. 데이터베이스를 생성합니다.

<br>

## **자동결제 실행방법**

- 자동결제를 실행하기 위해서는 매일 정해진 시간에 맞춰서 프로그램이 실행되어야 합니다.
- 아래 3가지 방법 중 상황에 맞는 방법을 적용해야 정상적으로 솔루션을 이용할 수 있습니다.


### **1. linux crontab**
- 리눅스 서버에 접속이 가능해서 crontab 설정을 직접 할 수 있을 때의 방법입니다.

#### **사용방법**

**1. 아래 명령어를 입력, 새 crontab 파일을 만들거나 기존 파일을 편집합니다.**
```bash
crontab -e
```

**2. i를 눌러 파일을 편집 가능한 상태로 만듭니다.**
<br>
![vim insert](./img/linux_crontab_1.png "title")


**3. 주기적으로 실행할 시간을 입력합니다.**
```vim
# crontab의 구조는 다음과 같습니다.
# 순서대로 {분} {시} {일} {월} {요일} {실행 명령}
# * * * * * {실행 명령}

# 매일 실행되야 하므로 {분}, {시}, {실행 명령}만 수정합니다.
# Ex) 매일 01시 30분에 /bbs/subscription/batch_service.php 경로의 파일을 실행
30 1 * * * /bbs/subscription/batch_service.php
```

**4. esc키를 누른 이후 아래 명령어를 입력해서 저장합니다.**
```vim
:wq
```

**5. crontab이 정상적으로 실행되는지 관리자페이지에서 확인합니다.**

<br>

### **2. cron job (https://console.cron-job.org)**
- 웹호스팅 환경이거나 크론탭을 사용할 수 없는 경우, 온라인으로 크론탭을 서비스해주는 사이트를 이용합니다. 외부 사이트를 사용하는 것이기 때문에 주기적으로  정상적으로 동작하는지 확인이 필요합니다.

#### **사용방법**

**1. 회원가입을 진행합니다. (https://console.cron-job.org/signup)**

**1.1 이름/이메일/비밀번호를 입력, 동의 후 'CREATE ACCOUNT'을 클릭합니다.**
<br>
![join](./img/cronjob_join.png)

**1.2 입력한 이메일로 인증메일이 발송됩니다. 'Activate account'을 클릭해서 인증합니다.**
<br>
![email_auth](./img/cronjob_email_auth.png)

**2. 다시 [홈페이지](https://console.cron-job.org)에 접속해서 로그인하면 메인화면을 볼 수 있습니다.**
<br>
![dashboard](./img/cronjob_main.png)

**3. '좌측 메뉴 > Cronjobs' 클릭, Cron을 등록/확인 할 수 있는 화면으로 접속합니다.**
<br>
![Cronjobs](./img/cronjob_main_left_menu.png)

**4. '우측 상단 > CREATE CRONJOB' 버튼 클릭**

**5. Title(제목), URL을 입력합니다.**
```
# URL에는 {사이트URL}/bbs/subscription/ajax.batch_service.php 을 입력합니다.
# http:// 또는 https:// 까지 붙여서 넣어주셔야 합니다.
```

**6. Save responses in job history 버튼을 눌러 주황색으로 활성화합니다.**
```
# Cron이 실행된 기록을 남기는 옵션입니다.
```

**7. 'Every day at'에서 실행될 시간을 설정합니다.**
```
# 자동결제는 매일 일정한 시간에 실행되어야 합니다.
# 결제가 실행되기 전까지 사용자는 서비스를 이용할 수 없으므로, 사용자가 적은 00:00 ~ 02:00사이로 설정하시길 권장합니다.
```

**8. 우측 하단의 'CREATE' 버튼을 클릭해서 저장합니다.**
<br>
![Cronjobs](./img/cronjob_create_set_time.png)

**9. crontab이 정상적으로 등록되었습니다.**
<br>
![Cronjobs](./img/cronjob_list.png)

<br>

### **3. 관리자페이지 실행**
- 자동결제 실행 파일을 직접 실행할 수 있는 방법입니다.
  
#### **사용방법**

**1. 관리자페이지 > 좌측메뉴 > 정기결제 관리 > 자동결제 실행기록에 접속합니다.**

**2. 우측 상단의 '결제 크론 수동실행' 버튼을 클릭합니다.**

**3. 목록에서 실행결과를 확인합니다.**