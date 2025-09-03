import "./app.scss";
import { createBrowserRouter, Outlet, RouterProvider } from "react-router-dom";
import React from "react";
import Navbar from "./components/navbar/Navbar";
import Footer from "./components/footer/Footer";
import Home from "./pages/home/Home";
import Gigs from "./pages/gigs/Gigs";
import Gig from "./pages/gig/Gig";
import Login from "./pages/login/Login";
import Register from "./pages/register/Register";
import Add from "./pages/add/Add";
import Orders from "./pages/orders/Orders";
import Messages from "./pages/messages/Messages";
import Message from "./pages/message/Message";
import MyGigs from "./pages/myGigs/MyGigs";
import DashboardRouter from "./components/DashboardRouter";
import AdminDashboard from "./pages/admin/AdminDashboard";
import ManageUsers from "./pages/admin/ManageUsers";
import ManageGigs from "./pages/admin/ManageGigs";
import ManageCategories from "./pages/admin/ManageCategories";
import SellerDashboard from "./pages/seller/SellerDashboard";
import BuyerDashboard from "./pages/buyer/BuyerDashboard";
import ManageOrders from "./pages/admin/ManageOrders";
import SingleOrder from "./pages/orders/SingleOrder";
import BuyerOrders from "./pages/orders/buyerOrders";
import ReviewOrder from "./pages/buyer/ReviewOrder";

function App() {
  const Layout = () => {
    return (
      <div className="app">
        <Navbar />
        <Outlet />
        <Footer />
      </div>
    );
  };

  const router = createBrowserRouter([
    {
      path: "/",
      element: <Layout />,
      children: [
        {
          path: "/",
          element: <Home />,
        },

        {
          path: "/myGigs",
          element: <MyGigs />,
        },
        {
          path: "/orders",
          element: <Orders />,
        },
        {
          path: "/order/:id",
          element: <SingleOrder />,
        },
        {
          path: "/messages",
          element: <Messages />,
        },
        {
          path: "/message/:id",
          element: <Message />,
        },
        {
          path: "/add",
          element: <Add />,
        },
        {
          path: "/gig/:id",
          element: <Gig />,
        },
        {
          path: "/gigs",
          element: <Gigs />,
        },
        {
          path: "/dashboard",
          element: <DashboardRouter />,
        },
        {
          path: "/admin",
          element: <AdminDashboard />,
        },
        {
          path: "/admin/users",
          element: <ManageUsers />,
        },
        {
          path: "/admin/orders",
          element: <ManageOrders />,
        },
        {
          path: "/admin/categories",
          element: <ManageCategories />,
        },
        {
          path: "/admin/gigs",
          element: <ManageGigs />,
        },
        {
          path: "/seller/dashboard",
          element: <SellerDashboard />,
        },
        {
          path: "/buyer/dashboard",
          element: <BuyerDashboard />,
        },
        {
          path: "/buyer/buyerOrders",
          element: <BuyerOrders />,
        },
        {
          path: "/orders/:orderId/review",
          element: <ReviewOrder />,
        },
      ],
    },
    {
      path: "/register",
      element: <Register />,
    },
    {
      path: "/login",
      element: <Login />,
    },
  ]);

  return <RouterProvider router={router} />;
}

export default App;
