import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import "./Home.scss";
import Featured from "../../components/featured/Featured";
import TrustedBy from "../../components/trustedBy/TrustedBy";
import Slide from "../../components/slide/Slide";
import CatCard from "../../components/catCard/CatCard";
import ProjectCard from "../../components/projectCard/ProjectCard";
import { projects } from "../../data";
import axios from "axios";

function Home() {
  const [categories, setCategories] = useState([]);
  const [loadingCategories, setLoadingCategories] = useState(true);

  // Fetch categories on component mount
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await axios.get(
          "https://marketplace.brainstone.xyz/api/categories/crud.php?status=active&limit=8"
        );
        if (response.data.status === "success") {
          // Transform categories to match the card structure expected by CatCard
          const transformedCategories = response.data.data.map((category) => ({
            id: category.id,
            title: category.name,
            desc: category.description || `Explore ${category.name}`,
            img: category.image
              ? `https://marketplace.brainstone.xyz/api/${category.image}`
              : `https://images.pexels.com/photos/7532110/pexels-photo-7532110.jpeg?auto=compress&cs=tinysrgb&w=1600&lazy=load`,
            category: category.name, // Add category name for linking
            slug: category.slug,
          }));
          setCategories(transformedCategories);
        } else {
          console.error("Failed to fetch categories:", response.data.message);
        }
      } catch (error) {
        console.error("Error fetching categories:", error);
      } finally {
        setLoadingCategories(false);
      }
    };

    fetchCategories();
  }, []);

  return (
    <div className="home">
      <Featured />
      <TrustedBy />
      <Slide slidesToShow={5} arrowsScroll={5}>
        {loadingCategories
          ? // Show loading placeholder cards
            Array.from({ length: 5 }, (_, index) => (
              <div key={index} className="loading-card">
                <div className="loading-img"></div>
                <div className="loading-text"></div>
                <div className="loading-title"></div>
              </div>
            ))
          : categories.map((card) => <CatCard key={card.id} card={card} />)}
      </Slide>
      <div className="features">
        <div className="container">
          <div className="item">
            <h1>A whole Bangladesh of freelance talent at your fingertips</h1>
            <div className="title">
              <img src="./img/check.png" alt="" />
              The best for every budget
            </div>
            <p>
              Find high-quality services at every price point. No hourly rates,
              just project-based pricing.
            </p>
            <div className="title">
              <img src="./img/check.png" alt="" />
              eQuality work done quickly
            </div>
            <p>
              Find the right freelancer to begin working on your project within
              minutes.
            </p>
            <div className="title">
              <img src="./img/check.png" alt="" />
              Protected payments, every time
            </div>
            <p>
              Always know what youll pay upfront. Your payment isnt released
              until you approve the work.
            </p>
            <div className="title">
              <img src="./img/check.png" alt="" />
              24/7 support
            </div>
            <p>
              Find high-quality services at every price point. No hourly rates,
              just project-based pricing.
            </p>
          </div>
          <div className="item">
            <video src="./video.mp4" controls autoPlay loop muted />
          </div>
        </div>
      </div>
      <div className="explore">
        <div className="container">
          <h1 style={{ marginBottom: "20px" }}>Explore the marketplace</h1>
          {/* <div className="items">
            {loadingCategories
              ? // Show loading placeholders
                Array.from({ length: 10 }, (_, index) => (
                  <div key={index} className="item loading">
                    <div className="loading-img"></div>
                    <div className="line"></div>
                    <div className="loading-text"></div>
                  </div>
                ))
              : categories.slice(0, 10).map(category => {
                  const categoryParam = category.slug || category.name;
                  const linkTo = `/gigs?cat=${encodeURIComponent(
                    categoryParam
                  )}`;

                  return (
                    <div
                      key={category.id}
                      className="item"
                      onClick={() => (window.location.href = linkTo)}
                      style={{ cursor: "pointer" }}
                    >
                      <img
                        src={
                          category.img ||
                          "https://fiverr-res.cloudinary.com/npm-assets/@fiverr/logged_out_homepage_perseus/apps/graphics-design.d32a2f8.svg"
                        }
                        alt={category.name}
                      />
                      <div className="line"></div>
                      <span>{category.name}</span>
                    </div>
                  );
                })}
          </div> */}

          {/* Categories Section */}
          <div className="categoriesSection">
            <div className="categoriesGrid">
              <Link
                to="/gigs?category=web-development"
                className="categoryCard">
                <div className="categoryIcon">💻</div>
                <h3>Web Development</h3>
                <p>Custom websites & web apps</p>
              </Link>
              <Link to="/gigs?category=graphic-design" className="categoryCard">
                <div className="categoryIcon">🎨</div>
                <h3>Graphic Design</h3>
                <p>Logos, banners & graphics</p>
              </Link>
              <Link
                to="/gigs?category=digital-marketing"
                className="categoryCard">
                <div className="categoryIcon">📱</div>
                <h3>Digital Marketing</h3>
                <p>SEO, social media & ads</p>
              </Link>
              <Link to="/gigs?category=writing" className="categoryCard">
                <div className="categoryIcon">✍️</div>
                <h3>Writing & Translation</h3>
                <p>Content writing & translation</p>
              </Link>
              <Link
                to="/gigs?category=video-animation"
                className="categoryCard">
                <div className="categoryIcon">🎬</div>
                <h3>Video & Animation</h3>
                <p>Video editing & animation</p>
              </Link>
              <Link to="/gigs?category=music-audio" className="categoryCard">
                <div className="categoryIcon">🎵</div>
                <h3>Music & Audio</h3>
                <p>Voice overs & music production</p>
              </Link>
            </div>
          </div>
        </div>
      </div>
      <div className="features dark">
        <div className="container">
          <div className="item">
            <h1>
              Freelancebd <i>business</i>
            </h1>
            <h1>
              A business solution designed for <i>teams</i>
            </h1>
            <p>
              Upgrade to a curated experience packed with tools and benefits,
              dedicated to businesses
            </p>
            <div className="title">
              <img src="./img/check.png" alt="" />
              Connect to freelancers with proven business experience
            </div>

            <div className="title">
              <img src="./img/check.png" alt="" />
              Get matched with the perfect talent by a customer success manager
            </div>

            <div className="title">
              <img src="./img/check.png" alt="" />
              Manage teamwork and boost productivity with one powerful workspace
            </div>
            <button>Explore Liverr Business</button>
          </div>
          <div className="item">
            <img
              src="https://marketplace.brainstone.xyz/api/uploads/Team.png"
              alt="Team Illustration"
            />
          </div>
        </div>
      </div>
      <Slide slidesToShow={4} arrowsScroll={4}>
        {projects.map((card) => (
          <ProjectCard key={card.id} card={card} />
        ))}
      </Slide>
    </div>
  );
}

export default Home;
