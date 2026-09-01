function Hero({ onStart }) {
  return (
    <section className="hero-section">
      <p className="eyebrow">✈️ TravelMatch</p>
      <h1>Find where and when you should travel based on your preferences.</h1>
      <p className="subtitle">
        Answer a few quick questions and we&apos;ll match you with destinations,
        the best time to go, and a realistic budget — personalized to you.
      </p>
      <button type="button" className="primary-button" onClick={onStart}>
        Start the quiz
      </button>
    </section>
  )
}

export default Hero
