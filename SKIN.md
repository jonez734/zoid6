# Input Prompt Color Templates
# Used for casino input*() functions

# inputstring - simple text input
# Usage: io.inputstring("{promptcolor}Prompt text{inputcolor}")
# Example: io.inputstring("{var:promptcolor}Moniker: {var:inputcolor}")
inputstring={var:promptcolor}{prompt}{var:inputcolor}

# inputinteger - numeric input
# Usage: io.inputinteger("{promptcolor}Prompt text{inputcolor}", default=10)
# Example: io.inputinteger("{var:promptcolor}Min bet: {var:inputcolor}", default=10)
inputinteger={var:promptcolor}{prompt}{var:inputcolor}

# inputboolean - yes/no input
# Usage: io.inputboolean("{promptcolor}Prompt text {optioncolor}[Yn]{promptcolor}: {inputcolor}", default="Y")
# Example: io.inputboolean("{var:promptcolor}Continue? {var:optioncolor}[Yn]{var:promptcolor}: {var:inputcolor}", "Y")
inputboolean={var:promptcolor}{prompt} {var:optioncolor}[{options}]{var:promptcolor}: {var:inputcolor}

# inputchoice - menu selection
# Usage: io.inputchoice("{promptcolor}Prompt text {optioncolor}[options]{promptcolor}: {inputcolor}", options, default="X")
# Example: io.inputchoice("{var:promptcolor}Game type: {var:optioncolor}[blackjack,poker]{var:promptcolor}: {var:inputcolor}", "blackjack,poker", default="blackjack")
# Note: Uppercase letter in options = default choice, e.g. [Q]uit means default is Q
inputchoice={var:promptcolor}{prompt} {var:optioncolor}[{options}]{var:promptcolor}: {var:inputcolor}
