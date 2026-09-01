import { useState } from 'react'
import { questions } from '../data/questions'
import QuestionCard from './QuestionCard'

function isAnswered(question, value) {
  if (question.type === 'multi') return Array.isArray(value) && value.length > 0
  if (question.type === 'range') return typeof value === 'number'
  return Boolean(value)
}

function Quiz({ onComplete }) {
  const [stepIndex, setStepIndex] = useState(0)
  const [answers, setAnswers] = useState({})

  const question = questions[stepIndex]
  const isLastStep = stepIndex === questions.length - 1
  const value = answers[question.id]
  const canProceed = isAnswered(question, value)

  const setAnswer = (newValue) => {
    setAnswers((prev) => ({ ...prev, [question.id]: newValue }))
  }

  const goNext = () => {
    if (!canProceed) return
    if (isLastStep) {
      onComplete(answers)
    } else {
      setStepIndex((i) => i + 1)
    }
  }

  const goBack = () => {
    setStepIndex((i) => Math.max(0, i - 1))
  }

  const progress = Math.round(((stepIndex + 1) / questions.length) * 100)

  return (
    <section className="quiz-section">
      <div className="progress-bar">
        <div className="progress-fill" style={{ width: `${progress}%` }} />
      </div>
      <p className="step-count">
        Question {stepIndex + 1} of {questions.length}
      </p>
      <h2>{question.question}</h2>

      <QuestionCard question={question} value={value} onChange={setAnswer} />

      <div className="quiz-nav">
        <button
          type="button"
          className="secondary-button"
          onClick={goBack}
          disabled={stepIndex === 0}
        >
          Back
        </button>
        <button
          type="button"
          className="primary-button"
          onClick={goNext}
          disabled={!canProceed}
        >
          {isLastStep ? 'See my matches' : 'Next'}
        </button>
      </div>
    </section>
  )
}

export default Quiz
