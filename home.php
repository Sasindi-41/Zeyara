<?php

include "connection.php";
session_start();

?>

<!DOCTYPE html>
<html lang="en"  data-bs-theme="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Zeyara Perfumes</title>
        <link rel="icon" href="resources/icon/perfume.png">
     <link href="bootstrap.css" rel="stylesheet" />
        
        <link href="style.css" rel="stylesheet" />
    </head>
    <body class="d-flex flex-column h-100">
        <main class="flex-shrink-0">
            <!-- nav bar  -->
            <?php
                include "navbar.php";
            ?>

            <!-- nav bar  -->
  
            <header class="bg-dark py-5">
                <div class="container px-5">
                    <div class="row gx-5 align-items-center justify-content-center">
                        <div class="col-lg-8 col-xl-7 col-xxl-6">
                            <div class="my-5 text-center text-xl-start">
                                <h1 class="display-5 fw-bolder text-white mb-2 text-center">Zeyara Perfumes</h1>
                                <p class="lead fw-normal text-white-50 mb-4 text-center">Srilankan Most Popular Perfumes</p>
                                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center justify-content-center">
                                    <a class="btn btn-outline-warning btn-lg px-4 me-sm-3 " href="index.php">Shop Now</a>
                                   
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-5 col-xxl-6 d-none d-xl-block text-center"><img class="img-fluid rounded-3 my-5" src="resources/sasi/pexels-didsss-1830450.jpg" alt="..." /></div>
                    </div>
                </div>
            </header>
           
           <section class="py-5" id="features">
    <div class="container px-5 my-5">
        <div class="row gx-5">
            <div class="col-lg-4 mb-5 mb-lg-0">
                <h2 class="fw-bolder mb-0">Discover the Essence of Luxury at Zeyara Perfume Store</h2>
            </div>
            <div class="col-lg-8">
                <div class="row gx-5 row-cols-1 row-cols-md-2">
                    <div class="col mb-5 h-100">
                        <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-collection"></i></div>
                        <h2 class="h5">Signature Collections</h2>
                        <p class="mb-0">Zeyara Perfume Store brings you a captivating range of signature fragrances crafted with passion and precision. From refreshing citrus notes to warm oriental blends, each perfume is designed to leave a lasting impression. Experience timeless elegance in every spray and indulge in scents that define your personality.</p>
                    </div>
                    <div class="col mb-5 h-100">
                        <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-magic"></i></div>
                        <h2 class="h5">Personalized Blends</h2>
                        <p class="mb-0">At Zeyara, we understand that every individual has a unique essence. That’s why we offer personalized fragrance customization, letting you design a perfume that truly reflects your style. Choose your favorite notes and create a scent as unique as you are.</p>
                    </div>
                    <div class="col mb-5 mb-md-0 h-100">
                        <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-gem"></i></div>
                        <h2 class="h5">Premium Quality</h2>
                        <p class="mb-0">We use only the finest natural ingredients and oils sourced from around the world. Every bottle of Zeyara perfume is carefully blended to ensure rich, long-lasting fragrances. With our premium quality promise, you can be confident that your scent will stay elegant all day long.</p>
                    </div>
                    <div class="col h-100">
                        <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-tags"></i></div>
                        <h2 class="h5">Affordable Luxury</h2>
                        <p class="mb-0">Luxury doesn’t have to come with an extravagant price. At Zeyara Perfume Store, we believe everyone deserves to enjoy the beauty of fine fragrances. Our perfumes are priced affordably without compromising on quality, so you can indulge in elegance every day.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


           <div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-xl-2 col-xxl-3 d-none d-xl-block text-center d-flex justify-content-center">
            <img class="img-fluid rounded-3 my-5" src="resources/products/13.jpg" alt="Zeyara Perfume" />
        </div>
    </div>
    <div>
        <div class="col h-100">
            <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3">
                <i class="bi bi-stars"></i>
            </div>

            <h2 class="h5 d-flex justify-content-center">About Us</h2>
            <p class="mb-0 text-center mt-4">
                Welcome to <strong>Zeyara Perfume Store</strong>, Sri Lanka’s trusted destination for luxury fragrances. 
                At Zeyara, we believe that a fragrance is more than just a scent—it’s a memory, an emotion, 
                and a reflection of your personality. Our mission is to help you find a signature scent that 
                tells your story with elegance and charm.
            </p>

            <h2 class="h5 d-flex justify-content-center mt-5">Our Story</h2>
            <p class="mb-0 text-center mt-4">
                Founded in 2015, Zeyara Perfume Store began with a vision of making premium perfumes accessible to everyone. 
                What started as a small boutique with a passion for unique fragrances has now grown into a 
                leading perfume retailer. Today, we proudly bring you an exclusive range of authentic perfumes, 
                blending global luxury with affordable elegance.
            </p>

            <h2 class="h5 d-flex justify-content-center mt-5">Our Products</h2>
            <p class="mb-0 text-center mt-4">
                From bold, long-lasting scents to soft, refreshing fragrances, our collection offers something for every mood 
                and occasion. Explore international designer perfumes, elegant Arabian blends, and custom signature scents 
                crafted just for you. Each fragrance at Zeyara is carefully selected to ensure quality, authenticity, 
                and a truly unforgettable experience.
            </p>

            <h2 class="h5 d-flex justify-content-center mt-5">Contact Us</h2>
            <p class="mb-0 text-center mt-4">Address : No.254, Anula Road, Colombo, Sri Lanka</p>
            <p class="mb-0 text-center mt-2">Phone : +94 112 834 214</p>
            <p class="mb-0 text-center mt-2">Email : zeyaraperfumes@gmail.com</p>
        </div>
    </div>
</div>

            
           
        </main>
        <!-- Footer-->
        <footer class="bg-dark py-4 mt-auto ">
            <div class="container px-5">
                <div class="row align-items-center justify-content-center flex-column flex-sm-row">
                    <div class="col-auto"><div class="small m-0 text-white">&copy; 2025 Zeyara Perfumes || All Right Reserved</div></div>
                    
                </div>
            </div>
        </footer>
       
        <script src="bootstrap.bundle.min.js"></script>
      
        <script src="script.js"></script>
    </body>
</html>
