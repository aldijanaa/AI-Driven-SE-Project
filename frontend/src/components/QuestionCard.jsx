function QuestionCard({ question, value, onChange }) {
  if (question.type === 'single') {
    return (
      <div className="options-grid">
        {question.options.map((option) => (
          <button
            key={option.value}
            type="button"
            className={`option-button ${value === option.value ? 'selected' : ''}`}
            onClick={() => onChange(option.value)}
          >
            {option.label}
          </button>
        ))}
      </div>
    )
  }

  if (question.type === 'multi') {
    const selected = value || []
    const maxSelect = question.maxSelect ?? selected.length + 1

    const toggle = (optionValue) => {
      if (selected.includes(optionValue)) {
        onChange(selected.filter((v) => v !== optionValue))
      } else if (selected.length < maxSelect) {
        onChange([...selected, optionValue])
      }
    }

    return (
      <div className="options-grid">
        {question.options.map((option) => {
          const isSelected = selected.includes(option.value)
          const isDisabled = !isSelected && selected.length >= maxSelect
          return (
            <button
              key={option.value}
              type="button"
              className={`option-button ${isSelected ? 'selected' : ''}`}
              disabled={isDisabled}
              onClick={() => toggle(option.value)}
            >
              {option.label}
            </button>
          )
        })}
      </div>
    )
  }

  if (question.type === 'range') {
    const current = value ?? question.default
    return (
      <div className="range-wrapper">
        <input
          type="range"
          min={question.min}
          max={question.max}
          step={question.step}
          value={current}
          onChange={(e) => onChange(Number(e.target.value))}
        />
        <div className="range-value">€{current}</div>
      </div>
    )
  }

  return null
}

export default QuestionCard
