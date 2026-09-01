import { useState } from 'react'
import { sendResultsByEmail } from '../api/travelApi'

function EmailCapture({ results }) {
  const [email, setEmail] = useState('')
  const [status, setStatus] = useState('idle') // idle | sending | sent | error
  const [errorMessage, setErrorMessage] = useState('')

  const handleSubmit = async (e) => {
    e.preventDefault()
    setStatus('sending')
    try {
      await sendResultsByEmail(email, results)
      setStatus('sent')
    } catch (err) {
      setErrorMessage(err.message)
      setStatus('error')
    }
  }

  if (status === 'sent') {
    return <p className="email-capture-sent">Sent! Check your inbox for your recommendations.</p>
  }

  return (
    <form className="email-capture" onSubmit={handleSubmit}>
      <label htmlFor="email">Want these emailed to you?</label>
      <div className="email-capture-row">
        <input
          id="email"
          type="email"
          required
          placeholder="you@example.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
        <button type="submit" className="secondary-button" disabled={status === 'sending'}>
          {status === 'sending' ? 'Sending…' : 'Send'}
        </button>
      </div>
      {status === 'error' && <p className="email-capture-error">{errorMessage}</p>}
    </form>
  )
}

export default EmailCapture
