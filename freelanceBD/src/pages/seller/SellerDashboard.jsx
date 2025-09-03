import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import "./SellerDashboard.scss";

const SellerDashboard = () => {
  const [stats, setStats] = useState({
    totalGigs: 0,
    activeOrders: 0,
    totalEarnings: 0,
    completedOrders: 0,
    averageRating: 0,
    totalReviews: 0,
  });

  const [recentOrders, setRecentOrders] = useState([]);
  const [recentGigs, setRecentGigs] = useState([]);
  const [notifications, setNotifications] = useState([]);

  useEffect(() => {
    fetchSellerData();
  }, []);

  const fetchSellerData = async () => {
    try {
      // Fetch seller statistics
      const statsResponse = await fetch(
        "https://marketplace.brainstone.xyz/api/seller/dashboard-stats.php",
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
        "https://marketplace.brainstone.xyz/api/seller/recent-orders.php",
        {
          credentials: "include",
        }
      );
      const ordersData = await ordersResponse.json();
      if (ordersData.status === "success") {
        setRecentOrders(ordersData.data);
      }

      // Fetch recent gigs
      const gigsResponse = await fetch(
        "https://marketplace.brainstone.xyz/api/seller/recent-gigs.php",
        {
          credentials: "include",
        }
      );
      const gigsData = await gigsResponse.json();
      if (gigsData.status === "success") {
        setRecentGigs(gigsData.data);
      }

      // Fetch notifications
      const notificationsResponse = await fetch(
        "https://marketplace.brainstone.xyz/api/seller/notifications.php",
        {
          credentials: "include",
        }
      );
      const notificationsData = await notificationsResponse.json();
      if (notificationsData.status === "success") {
        setNotifications(notificationsData.data);
      }
    } catch (error) {
      console.error("Error fetching seller data:", error);
    }
  };

  return (
    <div className="sellerDashboard">
      <div className="container">
        <div className="header">
          <h1>Seller Dashboard</h1>
          <div className="breadcrumb">
            <Link to="/">Home</Link> / Seller Dashboard
          </div>
        </div>

        {/* Statistics Cards */}
        <div className="statsGrid">
          <div className="statCard">
            <div className="statIcon">💼</div>
            <div className="statInfo">
              <h3>{stats.totalGigs}</h3>
              <p>Total Gigs</p>
            </div>
          </div>
          <div className="statCard">
            <div className="statIcon">📦</div>
            <div className="statInfo">
              <h3>{stats.activeOrders}</h3>
              <p>Active Orders</p>
            </div>
          </div>
          <div className="statCard">
            <div className="statIcon">💰</div>
            <div className="statInfo">
              <h3>${stats.totalEarnings}</h3>
              <p>Total Earnings</p>
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
            <div className="statIcon">⭐</div>
            <div className="statInfo">
              <h3>{stats.averageRating}</h3>
              <p>Average Rating</p>
            </div>
          </div>
          <div className="statCard">
            <div className="statIcon">📝</div>
            <div className="statInfo">
              <h3>{stats.totalReviews}</h3>
              <p>Total Reviews</p>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="quickActions">
          <h2>Quick Actions</h2>
          <div className="actionButtons">
            <Link to="/add" className="actionBtn">
              <span>➕</span>
              Create New Gig
            </Link>
            <Link to="/myGigs" className="actionBtn">
              <span>💼</span>
              Manage Gigs
            </Link>
            <Link to="/orders" className="actionBtn">
              <span>📦</span>
              View Orders
            </Link>
            <Link to="/messages" className="actionBtn">
              <span>💬</span>
              Messages
            </Link>
            <Link to="/seller/analytics" className="actionBtn">
              <span>📊</span>
              Analytics
            </Link>
            <Link to="/seller/earnings" className="actionBtn">
              <span>💳</span>
              Earnings
            </Link>
          </div>
        </div>

        {/* Main Content Grid */}
        <div className="mainContent">
          {/* Recent Orders */}
          <div className="section">
            <div className="sectionHeader">
              <h2>Recent Orders</h2>
              <Link to="/orders" className="viewAll">
                View All
              </Link>
            </div>
            <div className="ordersList">
              {recentOrders.length > 0 ? (
                recentOrders.map((order) => (
                  <div key={order.id} className="orderItem">
                    <div className="orderInfo">
                      <h4>Order #{order.id}</h4>
                      <p>{order.gig_title}</p>
                      <span className="buyer">Buyer: {order.buyer_name}</span>
                    </div>
                    <div className="orderMeta">
                      <span className="price">${order.price}</span>
                      <span className={`status ${order.status}`}>
                        {order.status}
                      </span>
                      <span className="deadline">
                        Due: {new Date(order.deadline).toLocaleDateString()}
                      </span>
                    </div>
                  </div>
                ))
              ) : (
                <p className="noData">No recent orders</p>
              )}
            </div>
          </div>

          {/* Recent Gigs */}
          <div className="section">
            <div className="sectionHeader">
              <h2>My Recent Gigs</h2>
              <Link to="/myGigs" className="viewAll">
                View All
              </Link>
            </div>
            <div className="gigsList">
              {recentGigs.length > 0 ? (
                recentGigs.map((gig) => (
                  <div key={gig.id} className="gigItem">
                    <div className="gigInfo">
                      <h4>{gig.title}</h4>
                      <p className="price">${gig.price}</p>
                      <div className="gigStats">
                        <span className={`status ${gig.status}`}>
                          {gig.status}
                        </span>
                        <span>
                          Created:{" "}
                          {new Date(gig.created_at).toLocaleDateString()}
                        </span>
                      </div>
                    </div>
                  </div>
                ))
              ) : (
                <p className="noData">No gigs created yet</p>
              )}
            </div>
          </div>
        </div>

        {/* Notifications */}
        <div className="notifications">
          <h2>Recent Notifications</h2>
          <div className="notificationsList">
            {notifications.length > 0 ? (
              notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`notification ${notification.type}`}>
                  <div className="notificationIcon">
                    {notification.type === "order" && "📦"}
                    {notification.type === "message" && "💬"}
                    {notification.type === "review" && "⭐"}
                    {notification.type === "payment" && "💰"}
                  </div>
                  <div className="notificationContent">
                    <p>{notification.message}</p>
                    <span className="time">
                      {new Date(notification.created_at).toLocaleString()}
                    </span>
                  </div>
                </div>
              ))
            ) : (
              <p className="noData">No new notifications</p>
            )}
          </div>
        </div>

        {/* Performance Chart Placeholder */}
        <div className="performanceChart">
          <h2>Performance Overview</h2>
          <div className="chartPlaceholder">
            <p>📈 Performance chart will be displayed here</p>
            <div className="chartStats">
              <div className="chartStat">
                <h3>This Month</h3>
                <p>${stats.totalEarnings}</p>
              </div>
              <div className="chartStat">
                <h3>Orders</h3>
                <p>{stats.completedOrders}</p>
              </div>
              <div className="chartStat">
                <h3>Rating</h3>
                <p>{stats.averageRating}/5</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SellerDashboard;
