import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import "./Featured.scss";

function Featured() {
  const [query, setQuery] = useState("");
  const [suggestions, setSuggestions] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);

  // API থেকে সাজেশন লোড
  useEffect(() => {
    if (query.trim() === "") {
      setSuggestions([]);
      return;
    }

    const delayDebounce = setTimeout(() => {
      fetch(
        `https://marketplace.brainstone.xyz/api/search/search.php?q=${query}`
      )
        .then((res) => res.json())
        .then((data) => {
          setSuggestions(data);
          setShowSuggestions(true);
        })
        .catch((err) => console.error(err));
    }, 300); // debounce

    return () => clearTimeout(delayDebounce);
  }, [query]);

  return (
    <div className="featured">
      <div className="container">
        <div className="left">
          <h1>
            Find the perfect <span>freelance</span> services for your business
          </h1>

          <div className="search">
            <div className="searchInput">
              <img src="./img/search.png" alt="" />
              <input
                type="text"
                placeholder='Try "building mobile app"'
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                onFocus={() => query && setShowSuggestions(true)}
              />
            </div>
            <button>Search</button>

            {showSuggestions && suggestions.length > 0 && (
              <ul className="suggestions">
                {suggestions.map((item) => (
                  <li key={item.id}>
                    <Link
                      to={`/gigs?cat=${item.name}`}
                      style={{
                        display: "flex",
                        alignItems: "center",
                        textDecoration: "none",
                        color: "black",
                      }}>
                      <img
                        src={
                          item.image
                            ? `https://marketplace.brainstone.xyz/api/${item.image}`
                            : "./img/ceo1.png"
                        }
                        alt={item.name}
                      />
                      <span>{item.name}</span>
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </div>

          <div className="popular">
            <span>Popular:</span>
            <button>Web Design</button>
            <button>WordPress</button>
            <button>Logo Design</button>
            <button>AI Services</button>
          </div>
        </div>

        <div className="right">
          <img src="./img/ceo1.png" alt="" />
        </div>
      </div>
    </div>
  );
}

export default Featured;
