# Bắt đầu từ image PHP có sẵn Apache
FROM php:8.2-apache

# Cài tiện ích mở rộng cho MySQL (nếu cần kết nối MySQL)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# (Tùy chọn) Mở rộng: cài thêm tiện ích nếu cần, ví dụ:
# RUN apt-get update && apt-get install -y vim unzip

# Copy toàn bộ mã nguồn vào thư mục server Apache
COPY . /var/www/html/

# Mở port 80 để Render map
EXPOSE 80

# Khởi động Apache khi container chạy
CMD ["apache2-foreground"]