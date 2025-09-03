import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import "./BuyerDashboard.scss";

const BuyerDashboard = () => {
  const [stats, setStats] = useState({
    activeOrders: 0,
    completedOrders: 0,
    totalSpent: 0,
    savedGigs: 0,
    unreadMessages: 0,
    pendingReviews: 0,
  });

  const [recentOrders, setRecentOrders] = useState([]);
  const [recommendedGigs, setRecommendedGigs] = useState([]);
  const [recentActivity, setRecentActivity] = useState([]);

  useEffect(() => {
    fetchBuyerData();
  }, []);

  const fetchBuyerData = async () => {
    try {
      // Fetch buyer statistics
      const statsResponse = await fetch(
        "https://marketplace.brainstone.xyz/api/buyer/dashboard-stats.php",
        {
          credentials: "include",
        }
      );
      const statsData = await statsResponse.json();
      if (statsData.status === "success") {
        setStats(statsData.data);
      }

      // Fetch recent orders
      const ordersResponse = await fetch(
        "https://marketplace.brainstone.xyz/api/buyer/recent-orders.php",
        {
          credentials: "include",
        }
      );
      const ordersData = await ordersResponse.json();
      if (ordersData.status === "success") {
        setRecentOrders(ordersData.data.slice(0, 5));
      }

      // Fetch recommended gigs
      // const gigsResponse = await fetch(
      //   "https://marketplace.brainstone.xyz/api/buyer/recommended-gigs.php",
      //   {
      //     credentials: "include",
      //   }
      // );
      // const gigsData = await gigsResponse.json();
      // if (gigsData.status === "success") {
      //   setRecommendedGigs(gigsData.data);
      // }

      // Fetch recent activity
      // const activityResponse = await fetch(
      //   "https://marketplace.brainstone.xyz/api/buyer/recent-activity.php",
      //   {
      //     credentials: "include",
      //   }
      // );
      // const activityData = await activityResponse.json();
      // if (activityData.status === "success") {
      //   setRecentActivity(activityData.data);
      // }
    } catch (error) {
      console.error("Error fetching buyer data:", error);
    }
  };

  const handleConfirmDelivery = async (orderId) => {
    try {
      const response = await fetch(
        "https://marketplace.brainstone.xyz/api/orders/confirm_delivery.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({ order_id: orderId }),
        }
      );
      const data = await response.json();
      if (data.status === "success") {
        // Refresh the data
        fetchBuyerData();
        alert("Order confirmed successfully!");
      } else {
        alert("Error: " + data.message);
      }
    } catch (error) {
      console.error("Error confirming delivery:", error);
      alert("Error confirming delivery");
    }
  };

  // console.log("Recent Orders:", recentOrders);

  return (
    <div className="buyerDashboard">
      <div className="container">
        <div className="header">
          <h1>Buyer Dashboard</h1>
          <div className="breadcrumb">
            <Link to="/">Home</Link> / Buyer Dashboard
          </div>
        </div>

        {/* Statistics Cards */}
        <div className="statsGrid">
          <div className="statCard">
            <div className="statIcon">📦</div>
            <div className="statInfo">
              <h3>{stats.activeOrders}</h3>
              <p>Active Orders</p>
            </div>
          </div>
          <div className="statCard">
            <div className="statIcon">✅</div>
            <div className="statInfo">
              <h3>{stats.completedOrders}</h3>
              <p>Completed Orders</p>
            </div>
          </div>
          <div className="statCard">
            <div className="statIcon">💰</div>
            <div className="statInfo">
              <h3>${stats.totalSpent}</h3>
              <p>Total Spent</p>
            </div>
          </div>
          <div className="statCard">
            <div className="statIcon">❤️</div>
            <div className="statInfo">
              <h3>{stats.savedGigs}</h3>
              <p>Saved Gigs</p>
            </div>
          </div>
          <div className="statCard">
            <div className="statIcon">💬</div>
            <div className="statInfo">
              <h3>{stats.unreadMessages}</h3>
              <p>Unread Messages</p>
            </div>
          </div>
          <div className="statCard">
            <div className="statIcon">⭐</div>
            <div className="statInfo">
              <h3>{stats.pendingReviews}</h3>
              <p>Pending Reviews</p>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="quickActions">
          <h2>Quick Actions</h2>
          <div className="actionButtons">
            <Link to="/gigs" className="actionBtn">
              <span>🔍</span>
              Browse Gigs
            </Link>
            <Link to="/orders" className="actionBtn">
              <span>📦</span>
              My Orders
            </Link>
            <Link to="/messages" className="actionBtn">
              <span>💬</span>
              Messages
            </Link>
            <Link to="/buyer/favorites" className="actionBtn">
              <span>❤️</span>
              Saved Gigs
            </Link>
            <Link to="/buyer/reviews" className="actionBtn">
              <span>⭐</span>
              My Reviews
            </Link>
            <Link to="/buyer/profile" className="actionBtn">
              <span>👤</span>
              My Profile
            </Link>
          </div>
        </div>

        {/* Main Content */}
        <div className="mainContent">
          {/* Recent Orders--------------------------------- */}
          <div className="section">
            <div className="sectionHeader">
              <h2>Recent Orders</h2>
            </div>
            <div className="ordersList">
              {recentOrders.length > 0 ? (
                recentOrders.map(
                  (order) => (
                    console.log(order),
                    (
                      <div key={order.order_id} className="orderItem">
                        {/* গিগ ইমেজ */}
                        <img
                          src={
                            " https://marketplace.brainstone.xyz/api/gigs/" +
                            order.gig_image
                          }
                          alt={order.gig_title}
                        />

                        {/* অর্ডার ইনফো */}
                        <div className="orderInfo">
                          <h4>{order.gig_title}</h4>
                          <p>Seller: {order.seller_name}</p>
                          <div className="orderMeta">
                            <span className="price">${order.price}</span>
                            <span className={`status ${order.status}`}>
                              {order.status === "delivered"
                                ? "order delivered"
                                : order.status}
                            </span>
                          </div>

                          {/* delivery summary show */}
                          {order.summary && (
                            <div className="deliveryInfo">
                              <p>
                                <strong>Delivery:</strong> {order.summary}
                              </p>
                              {order.files && (
                                <ul>
                                  {JSON.parse(order.files).map((file, i) => (
                                    <li key={i}>
                                      <a
                                        href={`https://marketplace.brainstone.xyz/api/orders/uploads/${file}`}
                                        target="_blank"
                                        rel="noreferrer">
                                        {file}
                                      </a>
                                    </li>
                                  ))}
                                </ul>
                              )}
                            </div>
                          )}
                        </div>

                        {/* Action buttons */}
                        <div className="orderActions">
                          {/* Show View button for delivered orders */}
                          {order.status === "delivered" && (
                            <Link
                              to={`/orders/${order.order_id}`}
                              className="viewBtn">
                              View
                            </Link>
                          )}

                          {/* Show Confirm button for delivered orders */}
                          {order.status === "delivered" && (
                            <button
                              style={{
                                marginLeft: "10px",
                                padding: "5px 10px",
                                backgroundColor: "#4CAF50",
                                color: "white",
                                border: "none",
                                borderRadius: "5px",
                              }}
                              onClick={() =>
                                handleConfirmDelivery(order.order_id)
                              }
                              className="confirmBtn">
                              Confirm
                            </button>
                          )}

                          {/* Show View Page button for completed orders */}
                          {order.status === "completed" && (
                            <Link
                              to={`/orders/${order.order_id}`}
                              className="viewBtn">
                              View Page
                            </Link>
                          )}

                          {/* Show Review button for completed orders that haven't been reviewed */}
                          {order.status === "completed" && !order.reviewed && (
                            <Link
                              to={`/orders/${order.order_id}/review`}
                              className="reviewBtn">
                              Review
                            </Link>
                          )}
                        </div>
                      </div>
                    )
                  )
                )
              ) : (
                <div className="noData">
                  <p>No orders yet</p>
                  <Link to="/gigs" className="browseBtn">
                    Browse Gigs
                  </Link>
                </div>
              )}
            </div>
          </div>

          {/* Recommended Gigs */}
          <div className="section">
            <div className="sectionHeader">
              <h2>Recommended for You</h2>
              <Link to="/gigs" className="viewAll">
                Browse All
              </Link>
            </div>
            <div className="gigsList">
              {recommendedGigs.length > 0 ? (
                recommendedGigs.map((gig) => (
                  <div key={gig.id} className="gigItem">
                    <img
                      src={gig.image_url || "/img/placeholder.jpg"}
                      alt={gig.title}
                    />
                    <div className="gigInfo">
                      <h4>{gig.title}</h4>
                      <p className="seller">by {gig.seller_name}</p>
                      <div className="gigMeta">
                        <div className="rating">
                          <span>⭐ {gig.rating || "New"}</span>
                          {gig.reviews_count && (
                            <span>({gig.reviews_count})</span>
                          )}
                        </div>
                        <span className="price">From ${gig.price}</span>
                      </div>
                    </div>
                    <div className="gigActions">
                      <Link to={`/gig/${gig.id}`} className="viewBtn">
                        View
                      </Link>
                      <button className="saveBtn">💖</button>
                    </div>
                  </div>
                ))
              ) : (
                <div className="noData">
                  <p>No recommendations available</p>
                  <Link to="/gigs" className="browseBtn">
                    Browse All Gigs
                  </Link>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Recent Activity */}
        <div className="recentActivity">
          <h2>Recent Activity</h2>
          <div className="activityList">
            {recentActivity.length > 0 ? (
              recentActivity.map((activity) => (
                <div key={activity.id} className="activityItem">
                  <div className="activityIcon">
                    {activity.type === "order" && "📦"}
                    {activity.type === "message" && "💬"}
                    {activity.type === "review" && "⭐"}
                    {activity.type === "payment" && "💰"}
                  </div>
                  <div className="activityContent">
                    <p>{activity.description}</p>
                    <span className="time">
                      {new Date(activity.created_at).toLocaleString()}
                    </span>
                  </div>
                </div>
              ))
            ) : (
              <p className="noData">No recent activity</p>
            )}
          </div>
        </div>

        {/* Categories Section */}
        <div className="categoriesSection">
          <h2>Popular Categories</h2>
          <div className="categoriesGrid">
            <Link to="/gigs?category=web-development" className="categoryCard">
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
            <Link to="/gigs?category=video-animation" className="categoryCard">
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
  );
};

export default BuyerDashboard;
