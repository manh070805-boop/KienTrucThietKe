# Cài đặt, vận hành và khôi phục

## Môi trường đã kiểm tra

Windows, Laragon; PHP 8.3.33 (pdo_mysql, fileinfo, gd, mbstring, intl), Laravel 12.70.0; Node 24.14.1, npm 11.11.0; React 19.3.1, TypeScript 7.0.2, Vite 7.3.7; MySQL Community 8.4.3. Phiên bản dependency nằm trong composer.lock/package-lock.json. Không nâng major framework.

PHP thực tế: D:/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe. MySQL: D:/laragon/bin/mysql/mysql-8.4.3-winx64/bin. Scripts dò thư mục PHP/MySQL, không giả định lệnh php luôn ở PATH.

## Dữ liệu hiện tại và an toàn

Trước triển khai, .env dùng SQLite mặc định database/database.sqlite. database/demo.sqlite, demo2.sqlite, demo3.sqlite và database.sqlite.bak là những tệp độc lập, không coi là cùng nguồn dữ liệu. Các tệp này được giữ nguyên; bản sao và .env cũ ở storage/app/backups. Không di chuyển hay reset database Laragon có sẵn.

MySQL Laragon tại 3306 không chạy lúc rà soát. Instance riêng được khởi tạo trong storage/app/mysql-data, chỉ bind 127.0.0.1:3307. Hai database student_feedback và student_feedback_test được tạo khi instance sạch. MySQL mặc định dùng InnoDB, utf8mb4_unicode_ci. Database khôi phục thử: student_feedback_restore_final_20261028, giữ lại để đối chiếu.

Mật khẩu root trống của instance riêng chỉ phục vụ local demo. Thông tin này không phải cấu hình triển khai Internet. Không đưa .env, backups hoặc mysql-data lên Git.

## Cài trên một bản checkout sạch

1. Cài PHP 8.3+, Composer, Node tương thích Vite 7, MySQL 8.4. Khởi động MySQL riêng bằng scripts/start-mysql.ps1 hoặc dùng MySQL Laragon và sửa cổng trong .env.
2. Đọc danh sách database bằng SHOW DATABASES trước khi tạo. Nếu student_feedback đã có dữ liệu khác, chọn tên mới và ghi tên vào .env; không reset. Tương tự với database test.
3. Chạy:

~~~powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm.cmd ci
~~~

Không sao chép .env.example đè lên .env đang sử dụng. Khóa APP_KEY mới chỉ sinh trên bản cài mới.

4. Tạo database bằng MySQL client (chỉ chạy tên chưa tồn tại):

~~~sql
CREATE DATABASE student_feedback CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE student_feedback_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
~~~

5. Cấu hình .env theo .env.example; sau đó:

~~~powershell
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
php artisan demo:verify
npm.cmd run typecheck
npm.cmd run build
.\run.bat
~~~

Migrations là nguồn schema. database/schema.sql được xuất từ MySQL đã migrate, dùng tham khảo/khôi phục vào database mới; không duy trì thiết kế thứ hai và không nhập đè database đang dùng.

Seeder chỉ dành cho local/testing hoặc môi trường e2e trên student_feedback_test. Nếu gặp email demo có sẵn bên ngoài bộ dữ liệu, seed dừng để tránh chiếm quyền/ghi đè tài khoản. Dataset đủ 100 UUID cố định thì chạy lại không sửa dữ liệu. Muốn một demo sạch mới, tạo database mới, không xóa dữ liệu hiện tại.

## Bật/tắt và URL

- run.bat: bật MySQL riêng nếu chưa chạy, dùng assets build, chạy Laravel tại http://127.0.0.1:8000.
- dev.bat: thêm Vite HMR tại 5173 (nền, log trong storage/logs/vite-*.log).
- Ctrl+C dừng Laravel/Vite của phiên foreground. Nếu 8000 đã chạy, run.bat giữ server đó; dev.bat chỉ bổ sung Vite và Ctrl+C dừng Vite của phiên.
- Dừng MySQL riêng: powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/stop-mysql.ps1.
- Server 8000 hiện bật nền. Dừng chính xác bằng: powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/stop-web.ps1 -Port 8000. PID record ở storage/logs/web-server-8000.json; script kiểm tra process/cổng/project trước khi dừng. Sau đó run.bat để bật lại. Không dừng mọi PHP/MySQL trên máy.
- Dùng nhất quán 127.0.0.1; đổi sang localhost có thể tạo cookie khác.
- Khi dùng Apache/Nginx Laragon, DocumentRoot phải là thư mục public, rewrite về public/index.php và cùng origin cho API/SPA.

Mọi thời gian database là UTC. Giao diện đổi sang Asia/Ho_Chi_Minh; bộ lọc ngày gửi hiểu theo ngày ở Việt Nam.

## Kiểm thử MySQL và browser

phpunit.xml chỉ định MySQL student_feedback_test. FeedbackTestCase từ chối chạy trên database khác, chạy migrations bổ sung và rollback fixture từng test. Test cạnh tranh dùng hai tiến trình PHP, commit fixture riêng để kết nối con đọc được rồi dọn đúng ID đã tạo; không reset database hoặc tắt FK.

~~~powershell
php artisan test --compact --log-junit storage/app/phpunit-results.xml
npm.cmd run typecheck
npm.cmd run build
~~~

E2E dùng .env.e2e (không commit) sao chép cấu hình local, đổi DB_DATABASE thành student_feedback_test, APP_URL thành http://127.0.0.1:8001 và SESSION_COOKIE thành student_feedback_e2e_session. Đặt PRIVATE_FILES_ROOT=D:/laragon/www/KienTrucThietKe/storage/app/e2e-private (đổi đường dẫn nếu checkout ở nơi khác) để không dùng chung tệp demo. Chạy với --env=e2e, không APP_ENV=testing, để middleware CSRF thực sự hoạt động.

~~~powershell
php artisan migrate --env=e2e --force
php artisan db:seed --env=e2e --force
php artisan serve --env=e2e --host=127.0.0.1 --port=8001 --no-reload
# Một terminal khác:
npm.cmd run test:e2e
~~~

Playwright dùng Google Chrome đã cài (channel chrome), 1 worker. Kết quả: storage/app/e2e-results.json; lỗi có trace/screenshot ở storage/app/playwright-results. Browser tests tạo hồ sơ/tài khoản giả mới trong database test, không thay đổi demo chính. Không chạy backend/E2E đồng thời trên database test. Không lấy thống kê test DB sau E2E làm thống kê dataset sạch.

## Sao lưu và restore

Sao lưu cả SQL lẫn tệp private. Dùng mysqldump --result-file để tránh PowerShell đổi encoding:

~~~powershell
$mysqlBin = 'D:\laragon\bin\mysql\mysql-8.4.3-winx64\bin'
& "$mysqlBin\mysqldump.exe" --no-defaults -h 127.0.0.1 -P 3307 -u root --single-transaction --routines --events --set-gtid-purged=OFF --column-statistics=0 --result-file=storage/app/backups/student_feedback.sql student_feedback
Copy-Item -LiteralPath storage/app/private/tickets -Destination storage/app/backups/private-tickets-new -Recurse
~~~

Kiểm tra tên database phục hồi CHƯA tồn tại rồi tạo tên mới, ví dụ student_feedback_restore_yyyymmdd. Import vào tên mới:

~~~powershell
& "$mysqlBin\mysql.exe" --no-defaults -h 127.0.0.1 -P 3307 -u root student_feedback_restore_yyyymmdd -e 'source D:/laragon/www/KienTrucThietKe/storage/app/backups/student_feedback.sql'
~~~

Đối chiếu COUNT từng bảng, AVG(score), FK/CHECK, tệp và metadata trước khi chuyển .env sang bản phục hồi. Không tự copy đè tệp private đang dùng. Lần triển khai đã phục hồi thành công vào student_feedback_restore_final_20261028: 100 hồ sơ, 12 đánh giá, AVG=3.7500, foreign_key_checks=1. Các bản sao local nằm trong storage/app/backups.

## Docker Compose (cấu hình bổ sung, chưa chạy trên máy này)

docker-compose.yml chỉ cung cấp MySQL 8.4 ở 3308, volume riêng và init database test. Không có Docker CLI trong môi trường đã kiểm tra nên chưa nghiệm thu Compose.

~~~powershell
docker compose up -d mysql
~~~

Khi dùng Docker, sửa DB_PORT=3308, DB_PASSWORD theo MYSQL_ROOT_PASSWORD; đồng bộ cấu hình test/worker tương ứng. Không chạy docker compose down -v với volume có dữ liệu. Frontend/backend tiếp tục chạy bằng PHP/Node local.

## Lỗi thường gặp

- Could not find driver: bật pdo_mysql cho đúng php.ini mà php --ini chỉ ra.
- Connection refused: bật MySQL, đối chiếu cổng .env (bản này là 3307), xem storage/logs/mysql.log.
- 419: tải lại trang/đăng nhập; client lấy CSRF mới sau login vì Laravel đổi token khi regenerate session.
- 401 luôn JSON cho /api/*, không redirect HTML. 403 là thiếu quyền hoặc tài khoản/thành viên đã ngừng hoạt động.
- 409: bản hồ sơ cũ; bấm tải lại phiên bản và đối chiếu trước khi gửi lại. API không tự ghi đè.
- Assets/HMR lỗi: dừng Vite, build lại; run.bat bỏ tệp public/hot cũ khi chạy assets build.
- Composer autoload: composer dump-autoload; root namespace App phải trỏ app. Phiên bản Pint hiện có cũng khai báo App, nhưng lớp project được ưu tiên; đã xác minh route/test bằng lớp project. Không sửa vendor bằng tay.
- Migration CHECK từ chối dữ liệu cũ: dừng, sao lưu, truy vấn bản ghi sai và chuyển đổi có chủ đích; không tắt kiểm tra hoặc tự xóa.
- Tệp thiếu: đối chiếu storage_key và disk local trong config/filesystems.php. Không chạy storage:link để công khai phản ánh.
- Dọn file mồ côi: php artisan files:cleanup (xem trước), sau đối chiếu dùng --delete; chỉ xóa file không được metadata tham chiếu và cũ hơn 24 giờ.
