import React, { useEffect, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import "./Navbar.scss";

function Navbar() {
  const [active, setActive] = useState(false);
  const [open, setOpen] = useState(false);
  const [currentUser, setCurrentUser] = useState(null);

  const { pathname } = useLocation();
  const imgUrl = "https://marketplace.brainstone.xyz/api/uploads/";

  const isActive = () => {
    window.scrollY > 0 ? setActive(true) : setActive(false);
  };

  useEffect(() => {
    window.addEventListener("scroll", isActive);
    checkUserSession();
    return () => {
      window.removeEventListener("scroll", isActive);
    };
  }, []);

  const checkUserSession = async () => {
    try {
      const response = await fetch(
        "https://marketplace.brainstone.xyz/api/auth/check-session.php",
        {
          credentials: "include",
        }
      );
      const data = await response.json();
      if (data.status === "success") {
        setCurrentUser(data.user);
      }
    } catch (error) {
      console.error("Error checking session:", error);
    }
  };

  // logout section
  const handleLogout = async () => {
    try {
      await fetch("https://marketplace.brainstone.xyz/api/auth/logout.php", {
        method: "POST",
        credentials: "include",
      });

      sessionStorage.removeItem("user");

      window.location.href = "/login";
    } catch (error) {
      console.error("Logout failed:", error);
    }
  };

  return (
    <div className={active || pathname !== "/" ? "navbar active" : "navbar"}>
      <div className="container">
        <div className="logo">
          <Link className="link" to="/">
            <span className="text">FreelanceBD</span>
          </Link>
          <span className="dot">.</span>
        </div>
        <div className="links">
          {/* Business Dropdown */}
          <div className="dropdown">
            <span>Business</span>
            <div className="dropdown-menu">
              <div className="column">
                <Link to="/business/teams">For Teams</Link>
                <Link to="/business/projects">Large Projects</Link>
                <Link to="/business/consulting">Consulting</Link>
              </div>
              <div className="column">
                <Link to="/business/vetted">Vetted Freelancers</Link>
                <Link to="/business/tools">Business Tools</Link>
                <Link to="/business/support">Priority Support</Link>
              </div>
            </div>
          </div>

          {/* Explore Dropdown */}
          <div className="dropdown">
            <span>Explore</span>
            <div className="dropdown-menu">
              <div className="column">
                <Link to="/explore/design">Graphics & Design</Link>
                <Link to="/explore/video">Video & Animation</Link>
                <Link to="/explore/writing">Writing & Translation</Link>
              </div>
              <div className="column">
                <Link to="/explore/marketing">Digital Marketing</Link>
                <Link to="/explore/tech">Programming & Tech</Link>
                <Link to="/explore/lifestyle">Lifestyle</Link>
              </div>
            </div>
          </div>

          <span>English</span>
          {currentUser?.role === "seller" ? (
            <Link className="link" to="/register">
              <span>Become a Buyer</span>
            </Link>
          ) : (
            <Link className="link" to="/register">
              <span>Become a Seller</span>
            </Link>
          )}
          {currentUser ? (
            <>
              <Link className="link" to="/dashboard">
                Dashboard
              </Link>
              {currentUser.role === "admin" && (
                <Link className="link" to="/admin">
                  Admin Panel
                </Link>
              )}
              <div className="user" onClick={() => setOpen(!open)}>
                <img
                  style={{ width: "32px", borderRadius: "50%" }}
                  src={imgUrl + currentUser.img || "/img/noavatar.jpg"}
                  alt=""
                />
                <span>{currentUser.username}</span>
                {open && (
                  <div className="options">
                    {currentUser.role === "seller" && (
                      <>
                        <Link className="link" to="/myGigs">
                          My Gigs
                        </Link>
                        <Link className="link" to="/add">
                          Add New Gig
                        </Link>
                      </>
                    )}
                    <Link className="link" to="/orders">
                      Orders
                    </Link>
                    <Link className="link" to="/messages">
                      Messages
                    </Link>
                    <Link className="link" onClick={handleLogout}>
                      Logout
                    </Link>
                  </div>
                )}
              </div>
            </>
          ) : (
            <>
              <Link className="link" to="/login">
                <button>Login</button>
              </Link>
              <Link className="link" to="/register">
                <button>Join</button>
              </Link>
            </>
          )}
        </div>
      </div>
      {(active || pathname !== "/") && (
        <>
          <hr />
          <div className="menu">
            <Link className="link menuLink" to="/">
              Graphics & Design
            </Link>
            <Link className="link menuLink" to="/">
              Video & Animation
            </Link>
            <Link className="link menuLink" to="/">
              Writing & Translation
            </Link>
            <Link className="link menuLink" to="/">
              AI Services
            </Link>
            <Link className="link menuLink" to="/">
              Digital Marketing
            </Link>
            <Link className="link menuLink" to="/">
              Music & Audio
            </Link>
            <Link className="link menuLink" to="/">
              Programming & Tech
            </Link>
            <Link className="link menuLink" to="/">
              Business
            </Link>
            <Link className="link menuLink" to="/">
              Lifestyle
            </Link>
          </div>
          <hr />
        </>
      )}
    </div>
  );
}

export default Navbar;
