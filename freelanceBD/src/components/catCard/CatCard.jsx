import React from "react";
import { Link } from "react-router-dom";
import "./CatCard.scss";

function CatCard({ card }) {
  // Create the link with the actual category name or slug
  const categoryParam = card.title;
  const linkTo = `/gigs?cat=${categoryParam}`;

  return (
    <Link to={linkTo}>
      <div className="catCard">
        <img src={card.img} alt={card.title} />
        <span className="desc">{card.desc}</span>
        <span className="title">{card.title}</span>
      </div>
    </Link>
  );
}
export default CatCard;
