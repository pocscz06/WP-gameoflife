import React from "react";
import "../styles/GameStats.css";

const GameStats = ({ generation = 0, population = 0, maxPopulation = 0 }) => {
  return (
    <div className="game-stats">
      <div className="stat-card">
        <h3>Generation</h3>
        <div className="stat-value">{generation}</div>
      </div>

      <div className="stat-card">
        <h3>Current Population</h3>
        <div className="stat-value">{population}</div>
      </div>

      <div className="stat-card">
        <h3>Max Population</h3>
        <div className="stat-value">{maxPopulation}</div>
      </div>
    </div>
  );
};

export default GameStats;
