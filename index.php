<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Sliders and Gallery</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css">
  <style>
    .swiper-container {
      width: 100%;
      height: 100%;
    }

    .swiper-slide {
      text-align: center;
      font-size: 18px;
      background: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .gallery-section {
      margin-top: 50px;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1 class="mt-5 mb-5 text-center">Manage Sliders and Gallery</h1>

    <div class="row">
      <div class="col-md-4">
        <!-- Форма для создания нового слайдера -->
        <form action="index.php" method="post" class="mb-4">
          <h2>Create Slider</h2>
          <div class="form-group">
            <label for="slider">Slider name:</label>
            <input type="text" name="slider_name" class="form-control" placeholder="Slider Name" required>
          </div>
          <button type="submit" name="create_slider" class="btn btn-primary">Create Slider</button>
        </form>
      </div>

      <div class="col-md-4">
        <!-- Форма для загрузки изображений в слайдер -->
        <form action="index.php" method="post" enctype="multipart/form-data" class="mb-4">
          <h2>Upload Image</h2>
          <div class="form-group">
            <label for="slider">Select slider for image:</label>
            <select name="slider" class="form-control" required>
              <?php
              $directories = array_filter(glob('images/*'), 'is_dir');
              foreach ($directories as $directory) {
                $dirName = basename($directory);
                echo "<option value=\"$dirName\">$dirName</option>";
              }
              ?>
            </select>
          </div>
          <div class="form-group">
            <input type="file" name="image" class="form-control-file" accept="image/*" required>
          </div>
          <button type="submit" name="upload_image" class="btn btn-primary">Upload Image</button>
        </form>
      </div>

      <div class="col-md-4">
        <!-- Форма для удаления слайдера -->
        <form action="index.php" method="post" class="mb-4">
          <h2>Delete Slider</h2>
          <div class="form-group">
            <label for="slider">Select slider for delete:</label>
            <select name="slider" class="form-control" required>
              <?php
              foreach ($directories as $directory) {
                $dirName = basename($directory);
                echo "<option value=\"$dirName\">$dirName</option>";
              }
              ?>
            </select>
          </div>
          <button type="submit" name="delete_slider" class="btn btn-danger">Delete Slider</button>
        </form>
      </div>
    </div>

    <?php
    function redirect(string $url)
    {
      header("Location: $url");
      exit();
    }

    function resizeImage(string $path, string $pathToSave, int $size = 150, bool $crop = false)
    {
      extract(pathinfo($path));
      $functionCreate = 'imagecreatefrom' . ($extension === 'jpg' ? 'jpeg' : $extension);
      $src = $functionCreate($path);
      list($src_width, $src_height) = getimagesize($path);

      if ($crop) {
        $dest = imagecreatetruecolor($size, $size);

        if ($src_width > $src_height) {
          imagecopyresampled($dest, $src, 0, 0, round($src_width / 2 - $src_height / 2), 0, $size, $size, $src_height, $src_height);
        } else {
          imagecopyresampled($dest, $src, 0, 0, 0, round($src_height / 2 - $src_width / 2), $size, $size, $src_width, $src_width);
        }
      } else {
        $dest_width = $size;
        $dest_height = $size;
        $dest = imagecreatetruecolor($dest_width, $dest_height);
        imagecopyresampled($dest, $src, 0, 0, 0, 0, $dest_width, $dest_height, $src_width, $src_height);
      }
      $functionSave = 'image' . ($extension === 'jpg' ? 'jpeg' : $extension);
      if (!file_exists($pathToSave)) {
        mkdir($pathToSave);
      }
      $functionSave($dest, "$pathToSave/$basename");

      imagedestroy($src);
      imagedestroy($dest);
    }

    function deleteDir($dirPath)
    {
      if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
      }
      if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
      }
      $files = glob($dirPath . '*', GLOB_MARK);
      foreach ($files as $file) {
        if (is_dir($file)) {
          deleteDir($file);
        } else {
          unlink($file);
        }
      }
      rmdir($dirPath);
    }

    // Обработка создания нового слайдера
    if (isset($_POST['create_slider'])) {
      $sliderName = trim($_POST['slider_name']);
      if (!empty($sliderName)) {
        $path = "images/$sliderName";
        if (!file_exists($path)) {
          mkdir($path, 0777, true);
          mkdir("$path/original", 0777, true);
          mkdir("$path/thumbs", 0777, true);
          $_SESSION['message'] = ['Slider created successfully!', 'success'];
        } else {
          $_SESSION['message'] = ['Slider already exists!', 'warning'];
        }
      }
      redirect('/index.php');
    }

    // Обработка загрузки изображений
    if (isset($_POST['upload_image'])) {
      $slider = $_POST['slider'];
      $image = $_FILES['image'];
      $targetDir = "images/$slider/original/";
      $thumbsDir = "images/$slider/thumbs/";
      $targetFile = $targetDir . basename($image['name']);
      if (move_uploaded_file(
        $image['tmp_name'],
        $targetFile
      )) {
        $thumbFile = $thumbsDir . basename($image['name']);
        resizeImage($targetFile, $thumbFile);
        redirect('/index.php');
      } else {
        redirect('/index.php');
      }
    }

    // Обработка удаления слайдера
    if (isset($_POST['delete_slider'])) {
      $slider = $_POST['slider'];
      $path = "images/$slider";
      deleteDir($path);
      redirect('/index.php');
    }
    ?>

    <div class="gallery-section">
      <h2>Gallery</h2>
      <?php
      foreach ($directories as $directory) {
        $sliderName = basename($directory);
        echo "<h3>$sliderName</h3>";
        echo '<div class="swiper-container">';
        echo '<div class="swiper-wrapper">';
        $thumbsDir = "$directory/thumbs";
        $images = glob("$thumbsDir/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        foreach ($images as $image) {
          $imageName = basename($image);
          $originalImage = "$directory/original/$imageName";
          echo '<div class="swiper-slide">';
          echo "<a data-fancybox=\"gallery\" href=\"$originalImage\"><img src=\"$image\" alt=\"$imageName\"></a>";
          echo '</div>';
        }
        echo '</div>';
        echo '<div class="swiper-pagination"></div>';
        echo '</div>';
        echo '<hr>';
      }
      ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function() {
      $('.swiper-container').each(function() {
        var swiper = new Swiper($(this)[0], {
          loop: true,
          pagination: {
            el: '.swiper-pagination',
          },
        });
      });
      $('[data-fancybox="gallery"]').fancybox({
        buttons: [
          "zoom",
          "slideShow",
          "thumbs",
          "close"
        ],
      });
    });
  </script>

</body>

</html>