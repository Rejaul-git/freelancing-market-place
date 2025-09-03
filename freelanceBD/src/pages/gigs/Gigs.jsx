import React, { useState, useEffect } from "react";
import { useLocation } from "react-router-dom";
import "./Gigs.scss";
import GigCard from "../../components/gigCard/GigCard";
import axios from "axios";

function Gigs() {
  const [gigs, setGigs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const location = useLocation();

  // Get category from URL query parameter
  const queryParams = new URLSearchParams(location.search);
  const category = queryParams.get("cat");

  useEffect(() => {
    const fetchGigs = async () => {
      setLoading(true);
      setError(null);

      try {
        // Fetch gigs from API based on category
        const response = await axios.get(
          `https://marketplace.brainstone.xyz/api/gigs/crud.php?category=${
            category || ""
          }&status=active`
        );

        if (response.data.status === "success") {
          // Transform API data to match GigCard component expectations
          const transformedGigs = response.data.data.map((gig) => ({
            id: gig.id,
            img:
              `https://marketplace.brainstone.xyz/api/gigs/${gig.cover}` ||
              (gig.images && gig.images.length > 0
                ? `https://marketplace.brainstone.xyz/api/gigs/${gig.images[0]}`
                : "https://images.pexels.com/photos/580151/pexels-photo-580151.jpeg?auto=compress&cs=tinysrgb&w=1600"),
            pp: gig.seller_img
              ? `https://marketplace.brainstone.xyz/api/uploads/${gig.seller_img}`
              : "https://images.pexels.com/photos/720598/pexels-photo-720598.jpeg?auto=compress&cs=tinysrgb&w=1600",
            desc: gig.short_description || gig.description,
            price: gig.price,
            star:
              gig.star_number > 0
                ? (gig.total_stars / gig.star_number).toFixed(1)
                : 0,
            username: gig.seller_name || "Unknown Seller",
          }));

          setGigs(transformedGigs);
        } else {
          setError(response.data.message || "Failed to fetch gigs");
        }
      } catch (err) {
        console.error("Error fetching gigs:", err);
        setError("Failed to fetch gigs. Please try again later.");
      } finally {
        setLoading(false);
      }
    };

    fetchGigs();
  }, [category]);

  return (
    <div className="gigs">
      <div className="container">
        <span className="breadcrumbs">
          freelancebd {">"} {category || "All Gigs"}
        </span>
        <h1>{category ? `${category} Gigs` : "All Gigs"}</h1>
        <p>
          {category
            ? `Explore the best ${category} gigs from talented freelancers`
            : "Explore thousands of gigs from talented freelancers"}
        </p>
        {error && <div className="error">{error}</div>}
        {loading ? (
          <div className="loading">Loading gigs...</div>
        ) : (
          <div className="gigs-list">
            {gigs.length > 0 ? (
              gigs.map((gig) => <GigCard key={gig.id} item={gig} />)
            ) : (
              <div className="no-gigs">No gigs found for this category.</div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

export default Gigs;
